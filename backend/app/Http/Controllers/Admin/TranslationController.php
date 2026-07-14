<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class TranslationController extends Controller
{
    /**
     * Display list of translation files
     */
    public function index()
    {
        $langPath = base_path('lang');
        $locales = ['ar', 'en', 'tr'];
        $files = ['admin', 'frontend', 'validation'];
        
        $translations = [];
        
        foreach ($locales as $locale) {
            foreach ($files as $file) {
                $filePath = $langPath . '/' . $locale . '/' . $file . '.php';
                if (File::exists($filePath)) {
                    $translations[$locale][$file] = [
                        'path' => $filePath,
                        'size' => File::size($filePath),
                        'modified' => File::lastModified($filePath),
                    ];
                }
            }
        }
        
        return view('admin.translations.index', compact('locales', 'files', 'translations'));
    }

    /**
     * Show translation file for editing
     */
    public function show($locale, $file)
    {
        // Validate locale
        if (!in_array($locale, ['ar', 'en', 'tr'])) {
            return redirect()->route('admin.translations.index')
                ->with('error', 'اللغة غير صحيحة');
        }
        
        // Validate file name
        if (!in_array($file, ['admin', 'frontend', 'validation'])) {
            return redirect()->route('admin.translations.index')
                ->with('error', 'اسم الملف غير صحيح');
        }
        
        $filePath = base_path('lang/' . $locale . '/' . $file . '.php');
        
        if (!File::exists($filePath)) {
            return redirect()->route('admin.translations.index')
                ->with('error', 'الملف غير موجود');
        }
        
        // Load the actual translations array
        $translations = include $filePath;
        
        // Convert to flat array for easier editing
        $flatTranslations = $this->arrayToFlat($translations);
        
        return view('admin.translations.show', compact('locale', 'file', 'translations', 'flatTranslations'));
    }

    /**
     * Update translation file
     */
    public function update(Request $request, $locale, $file)
    {
        // Validate locale
        if (!in_array($locale, ['ar', 'en', 'tr'])) {
            return back()->with('error', 'اللغة غير صحيحة');
        }
        
        // Validate file name
        if (!in_array($file, ['admin', 'frontend', 'validation'])) {
            return back()->with('error', 'اسم الملف غير صحيح');
        }
        
        $filePath = base_path('lang/' . $locale . '/' . $file . '.php');
        
        if (!File::exists($filePath)) {
            return back()->with('error', 'الملف غير موجود');
        }
        
        // Get translations from request
        $translations = $request->input('translations', []);
        
        // Convert flat array back to nested structure
        $nestedTranslations = $this->arrayToNested($translations);
        
        // Generate PHP file content
        $phpContent = $this->arrayToPhp($nestedTranslations);
        
        // Write to file
        try {
            File::put($filePath, $phpContent);
            
            ActivityLog::log('translation_updated', null, [
                'locale' => $locale,
                'file' => $file,
            ]);
            
            return redirect()->route('admin.translations.show', [$locale, $file])
                ->with('success', 'تم حفظ التعديلات بنجاح');
        } catch (\Exception $e) {
            return back()->with('error', 'حدث خطأ أثناء حفظ الملف: ' . $e->getMessage());
        }
    }

    /**
     * Convert flat array keys to nested array
     */
    private function arrayToNested(array $flatArray): array
    {
        $result = [];
        foreach ($flatArray as $key => $value) {
            if (empty($value) && $value !== '0') {
                continue; // Skip empty values
            }
            $keys = explode('.', $key);
            $current = &$result;
            
            foreach ($keys as $k) {
                if (!isset($current[$k])) {
                    $current[$k] = [];
                }
                $current = &$current[$k];
            }
            
            $current = $value;
        }
        return $result;
    }

    /**
     * Convert array to PHP file content
     */
    private function arrayToPhp(array $array): string
    {
        $output = "<?php\n\nreturn [\n";
        $output .= $this->arrayToString($array, 1);
        $output .= "];\n";
        return $output;
    }

    /**
     * Recursively convert array to string
     */
    private function arrayToString(array $array, int $indent = 0): string
    {
        $spaces = str_repeat('    ', $indent);
        $output = '';
        
        foreach ($array as $key => $value) {
            $output .= $spaces;
            
            // Key
            if (is_numeric($key)) {
                $output .= $key;
            } else {
                // Escape single quotes in key
                $output .= "'" . str_replace(["'", "\\"], ["\\'", "\\\\"], $key) . "'";
            }
            
            $output .= ' => ';
            
            // Value
            if (is_array($value)) {
                $output .= "[\n";
                $output .= $this->arrayToString($value, $indent + 1);
                $output .= $spaces . "],\n";
            } else {
                // Escape single quotes and handle special characters
                $escaped = str_replace(["'", "\\", "\n", "\r", "\t"], ["\\'", "\\\\", "\\n", "\\r", "\\t"], $value);
                $output .= "'" . $escaped . "',\n";
            }
        }
        
        return $output;
    }

    /**
     * Convert nested array to flat array for form
     */
    private function arrayToFlat(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $newKey = $prefix ? $prefix . '.' . $key : $key;
            
            if (is_array($value)) {
                $result = array_merge($result, $this->arrayToFlat($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }
        return $result;
    }
}
