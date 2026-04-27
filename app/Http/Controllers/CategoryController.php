<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('categories/Index', [
            'categories' => Category::query()
                ->withCount('books')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $category = Category::query()->create($request->validated());

        $this->toast("«{$category->name}» ангилал нэмэгдлээ.");

        return to_route('categories.index');
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        $this->toast('Ангилал хадгалагдлаа.');

        return to_route('categories.index');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $books = $category->books()->count();

        if ($books > 0) {
            $this->toast("«{$category->name}» ангилалд {$books} ном бүртгэлтэй тул устгах боломжгүй.", 'error');

            return to_route('categories.index');
        }

        $category->delete();

        $this->toast('Ангилал устгагдлаа.');

        return to_route('categories.index');
    }
}
