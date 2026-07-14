<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function index()
    {
        $faqs = Faq::ordered()->paginate(20);
        return view('admin.faqs.index', compact('faqs'));
    }

    public function create()
    {
        return view('admin.faqs.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'question_ar' => 'required|string|max:255',
            'question_en' => 'required|string|max:255',
            'question_tr' => 'required|string|max:255',
            'answer_ar' => 'required|string',
            'answer_en' => 'required|string',
            'answer_tr' => 'required|string',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $faq = Faq::create([
            'question_ar' => $request->question_ar,
            'question_en' => $request->question_en,
            'question_tr' => $request->question_tr,
            'answer_ar' => $request->answer_ar,
            'answer_en' => $request->answer_en,
            'answer_tr' => $request->answer_tr,
            'order' => $request->order ?? 0,
            'is_active' => $request->has('is_active'),
        ]);

        ActivityLog::log('faq_created', $faq);

        return redirect()->route('admin.faqs.index')
            ->with('success', 'تم إضافة السؤال الشائع بنجاح');
    }

    public function edit($id)
    {
        $faq = Faq::findOrFail($id);
        return view('admin.faqs.edit', compact('faq'));
    }

    public function update(Request $request, $id)
    {
        $faq = Faq::findOrFail($id);

        $request->validate([
            'question_ar' => 'required|string|max:255',
            'question_en' => 'required|string|max:255',
            'question_tr' => 'required|string|max:255',
            'answer_ar' => 'required|string',
            'answer_en' => 'required|string',
            'answer_tr' => 'required|string',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $oldData = $faq->toArray();
        
        $faq->update([
            'question_ar' => $request->question_ar,
            'question_en' => $request->question_en,
            'question_tr' => $request->question_tr,
            'answer_ar' => $request->answer_ar,
            'answer_en' => $request->answer_en,
            'answer_tr' => $request->answer_tr,
            'order' => $request->order ?? 0,
            'is_active' => $request->has('is_active'),
        ]);

        ActivityLog::log('faq_updated', $faq, [
            'old' => $oldData,
            'new' => $faq->toArray()
        ]);

        return redirect()->route('admin.faqs.index')
            ->with('success', 'تم تحديث السؤال الشائع بنجاح');
    }

    public function destroy($id)
    {
        $faq = Faq::findOrFail($id);
        
        ActivityLog::log('faq_deleted', $faq);
        
        $faq->delete();

        return back()->with('success', 'تم حذف السؤال الشائع بنجاح');
    }

    public function toggleStatus($id)
    {
        $faq = Faq::findOrFail($id);
        $faq->update(['is_active' => !$faq->is_active]);

        ActivityLog::log('faq_status_toggle', $faq, [
            'is_active' => $faq->is_active
        ]);

        return back()->with('success', 'تم تغيير حالة السؤال الشائع بنجاح');
    }
}
