<?php

namespace App\Http\Controllers;

use App\Services\FrontLang;
use App\Services\BackLang;
use App\Helpers\Response;

class LanguageController extends Controller {
    public function frontLanguage() {
        $lang = request()->header('lang') ?? app()->getLocale();

        // Get translations from both backend and frontend
        $frontTranslations = FrontLang::getTranslations($lang);
        $backTranslations = BackLang::getTranslations($lang);

        // Merge both translation arrays
        $allTranslations = array_merge($backTranslations, $frontTranslations);

        return Response::_200([
            'success' => true,
            'current_language' => $lang,
            'available_languages' => FrontLang::getLanguageList(),
            'translations' => $allTranslations,
        ])->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    public function list() {
        return Response::_200([
            'success' => true,
            'languages' => FrontLang::getLanguageList(),
        ]);
    }

    public function translations($lang) {
        // Get translations from both backend and frontend
        $frontTranslations = FrontLang::getTranslations($lang);
        $backTranslations = BackLang::getTranslations($lang);

        // Merge both translation arrays
        $allTranslations = array_merge($backTranslations, $frontTranslations);

        return Response::_200([
            'success' => true,
            'lang' => $lang,
            'translations' => $allTranslations,
        ]);
    }
}
