<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookRequest;
use App\Models\Book;
use App\Services\DigitalBookFactory;
use App\Services\PhysicalBookFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use App\Services\BorrowingService;

class BookController extends Controller
{
    public function __construct(
        private readonly BorrowingService $borrowingService
    ) {
    }

    /**
     * 检索图书列表 (支持 Web 页面与 API JSON 响应)
     */
    public function index(Request $request): View|JsonResponse
    {
        try {
            $query = Book::query();

            // 安全的参数化模糊查询 (ORM 防御 SQL 注入)
            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('author', 'like', "%{$search}%")
                      ->orWhere('isbn', 'like', "%{$search}%")
                      ->orWhere('category', 'like', "%{$search}%");
                });
            }

            // 按类型过滤
            if ($type = $request->input('type')) {
                $query->where('type', $type);
            }

            $books = $query->latest()->paginate(10)->withQueryString();

            // Web Service API 请求支持
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $books,
                ], Response::HTTP_OK);
            }

            // MVC View 渲染
            return view('books.index', compact('books'));

        } catch (Throwable $e) {
            Log::error('Failed to retrieve books list.', [
                'event' => 'BOOK_INDEX_ERROR',
                'error_message' => $e->getMessage(),
                'ip_address' => $request->ip(),
                'user_id' => $request->user()?->id,
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Internal server error. Unable to fetch books.',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return back()->with('error', 'Unable to retrieve books list at the moment.');
        }
    }

    /**
     * 渲染创建新图书的表单页面 (Web MVC)
     */
    public function create(): View
    {
        return view('books.create');
    }

    /**
     * 保存新建图书 (应用 Factory Method Pattern)
     */
    public function store(StoreBookRequest $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            // 根据图书类型委派给具体工厂 (Factory Method Pattern)
            $factory = match ($validated['type']) {
                'physical' => new PhysicalBookFactory(),
                'ebook' => new DigitalBookFactory(),
            };

            // 工厂实例化并持久化数据模型
            $book = $factory->registerBook(
                $validated,
                $request->file('cover_image'),
                $request->file('ebook_file')
            );

            DB::commit();

            // 关键业务事件日志 (Audit Trail & Logging)
            Log::info('Book created successfully.', [
                'event' => 'BOOK_CREATION_SUCCESS',
                'book_id' => $book->id,
                'isbn' => $book->isbn,
                'title' => $book->title,
                'type' => $book->type,
                'performed_by' => $request->user()?->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => now()->toIso8601String(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Book registered successfully.',
                    'data' => $book,
                ], Response::HTTP_CREATED);
            }

            return redirect()->route('books.index')->with('success', 'Book created successfully.');

        } catch (Throwable $e) {
            DB::rollBack();

            // 异常错误日志审计
            Log::error('Book creation failed.', [
                'event' => 'BOOK_CREATION_FAILED',
                'isbn' => $validated['isbn'] ?? 'N/A',
                'error_message' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
                'performed_by' => $request->user()?->id,
                'ip_address' => $request->ip(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while creating the book record.',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return back()->withInput()->with('error', 'Failed to register book: ' . $e->getMessage());
        }
    }

    /**
     * 查看单本图书详情
     */
    public function show(Request $request, int $id): View|JsonResponse
    {
        try {
            $book = Book::find($id);

            if (!$book) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Book not found.',
                    ], Response::HTTP_NOT_FOUND);
                }
                return redirect()->route('books.index')->with('error', 'Book not found.');
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $book,
                ], Response::HTTP_OK);
            }

            return view('books.show', compact('book'));

        } catch (Throwable $e) {
            Log::error('Failed to fetch book details.', [
                'event' => 'BOOK_SHOW_ERROR',
                'book_id' => $id,
                'error_message' => $e->getMessage(),
                'ip_address' => $request->ip(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Internal server error.',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return redirect()->route('books.index')->with('error', 'Error loading book details.');
        }
    }

    /**
     * 渲染编辑图书页面 (Web MVC)
     */
    public function edit(int $id): View|RedirectResponse
    {
        $book = Book::find($id);

        if (!$book) {
            return redirect()->route('books.index')->with('error', 'Book not found.');
        }

        return view('books.edit', compact('book'));
    }

    /**
     * 更新图书信息
     */
    public function update(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $book = Book::find($id);

        if (!$book) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Book not found.'], Response::HTTP_NOT_FOUND);
            }
            return redirect()->route('books.index')->with('error', 'Book not found.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'total_copies' => $book->isPhysical()
                ? [
                    'required',
                    'integer',
                    'min:0',
                    'max:10000',
                ]
                : [
                    'nullable',
                    'integer',
                    'min:0',
                    'max:10000',
                ],
        ]);

        DB::beginTransaction();

        try {
            if (
                $book->isPhysical()
                && isset($validated['total_copies'])
            ) {
                $book = $this->borrowingService
                    ->updateCopyQuantity(
                        $request->user(),
                        $book,
                        (int) $validated['total_copies']
                    );
            }

            $book->fill([
                'title' => $validated['title'],
                'author' => $validated['author'],
                'category' => $validated['category'],
            ])->save();

            DB::commit();

            Log::info('Book updated successfully.', [
                'event' => 'BOOK_UPDATE_SUCCESS',
                'book_id' => $book->id,
                'performed_by' => $request->user()?->id,
                'ip_address' => $request->ip(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Book updated successfully.',
                    'data' => $book,
                ], Response::HTTP_OK);
            }

            return redirect()->route('books.index')->with('success', 'Book updated successfully.');

        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Book update failed.', [
                'event' => 'BOOK_UPDATE_FAILED',
                'book_id' => $id,
                'error_message' => $e->getMessage(),
                'ip_address' => $request->ip(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update book.',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return back()->withInput()->with('error', 'Error updating book.');
        }
    }

    /**
     * 删除图书及关联物理文件 (Secure File Deletion)
     */
    public function destroy(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $book = Book::find($id);

        if (!$book) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Book not found.'], Response::HTTP_NOT_FOUND);
            }
            return redirect()->route('books.index')->with('error', 'Book not found.');
        }

        // 检查是否有未还副本
        if (
            $book->borrowings()->exists()
            || $book->reservations()->exists()
        ) {
            $message =
                'This book cannot be deleted because it has borrowing or reservation history.';

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            return back()->with('error', $message);
        }

        DB::beginTransaction();
        try {
            // 安全删除已上传的关联文件
            if ($book->cover_image_path && Storage::disk('public')->exists($book->cover_image_path)) {
                Storage::disk('public')->delete($book->cover_image_path);
            }

            if ($book->file_path && Storage::disk('local')->exists($book->file_path)) {
                Storage::disk('local')->delete($book->file_path);
            }

            $book->delete();

            DB::commit();

            Log::info('Book deleted successfully.', [
                'event' => 'BOOK_DELETION_SUCCESS',
                'book_id' => $id,
                'isbn' => $book->isbn,
                'performed_by' => $request->user()?->id,
                'ip_address' => $request->ip(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Book deleted successfully.',
                ], Response::HTTP_OK);
            }

            return redirect()->route('books.index')->with('success', 'Book deleted successfully.');

        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Book deletion failed.', [
                'event' => 'BOOK_DELETION_FAILED',
                'book_id' => $id,
                'error_message' => $e->getMessage(),
                'ip_address' => $request->ip(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete book.',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return back()->with('error', 'Failed to delete book.');
        }
    }
}