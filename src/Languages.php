<?php

namespace StreamsSro\Gaston;

/**
 * Constants mirrored from the Gaston API.
 *
 * These are kept in sync with the server so the client can validate input
 * locally before issuing a request.
 */
final class Languages
{
    /**
     * Languages accepted by the transcription endpoints (Whisper language codes).
     *
     * @var string[]
     */
    const SUPPORTED = array(
        'af', 'am', 'ar', 'as', 'az', 'ba', 'be', 'bg', 'bn', 'bo', 'br', 'bs', 'ca', 'cs', 'cy', 'da', 'de',
        'el', 'en', 'es', 'et', 'eu', 'fa', 'fi', 'fo', 'fr', 'gl', 'gu', 'ha', 'haw', 'he', 'hi', 'hr', 'ht',
        'hu', 'hy', 'id', 'is', 'it', 'ja', 'jw', 'ka', 'kk', 'km', 'kn', 'ko', 'la', 'lb', 'ln', 'lo', 'lt',
        'lv', 'mg', 'mi', 'mk', 'ml', 'mn', 'mr', 'ms', 'mt', 'my', 'ne', 'nl', 'nn', 'no', 'oc', 'pa', 'pl',
        'ps', 'pt', 'ro', 'ru', 'sa', 'sd', 'si', 'sk', 'sl', 'sn', 'so', 'sq', 'sr', 'su', 'sv', 'sw', 'ta',
        'te', 'tg', 'th', 'tk', 'tl', 'tr', 'tt', 'uk', 'ur', 'uz', 'vi', 'yi', 'yo', 'zh', 'yue',
    );

    /**
     * Languages the translation endpoint can translate into (short code => FLORES code).
     *
     * @var array<string, string>
     */
    const TRANSLATION_OPTIONS = array(
        'en' => 'eng_Latn', 'de' => 'deu_Latn', 'es' => 'spa_Latn', 'pl' => 'pol_Latn', 'hu' => 'hun_Latn',
        'cs' => 'ces_Latn', 'sk' => 'slk_Latn', 'uk' => 'ukr_Cyrl', 'bg' => 'bul_Cyrl', 'hr' => 'hrv_Latn',
        'da' => 'dan_Latn', 'nl' => 'nld_Latn', 'et' => 'est_Latn', 'fi' => 'fin_Latn', 'fr' => 'fra_Latn',
        'el' => 'ell_Grek', 'it' => 'ita_Latn', 'lv' => 'lav_Latn', 'lt' => 'lit_Latn', 'mt' => 'mlt_Latn',
        'pt' => 'por_Latn', 'ro' => 'ron_Latn', 'sl' => 'slv_Latn', 'sv' => 'swe_Latn', 'zh' => 'zho_Hans',
        'ar' => 'arb_Arab', 'hi' => 'hin_Deva', 'ja' => 'jpn_Jpan', 'id' => 'ind_Latn', 'is' => 'isl_Latn',
        'he' => 'heb_Hebr', 'kk' => 'kaz_Cyrl', 'ko' => 'kor_Hang', 'lb' => 'ltz_Latn', 'mk' => 'mkd_Cyrl',
        'tr' => 'tur_Latn', 'vi' => 'vie_Latn', 'bn' => 'ben_Beng', 'be' => 'bel_Cyrl', 'ka' => 'kat_Geor',
        'fa' => 'pes_Arab', 'ur' => 'urd_Arab', 'te' => 'tel_Telu', 'ru' => 'rus_Cyrl',
    );

    /** Possible values of Media::$state. */
    const STATE_PENDING = 'pending';
    const STATE_UPLOADED = 'uploaded';
    const STATE_TRANSCRIBED = 'transcribed';

    /** Possible values of Media::$origin. */
    const ORIGIN_UPLOADED = 'up';
    const ORIGIN_YOUTUBE = 'yt';
    const ORIGIN_WEB = 'web';

    private function __construct()
    {
    }

    /**
     * Languages available as translation targets.
     *
     * @return string[]
     */
    public static function translationLanguages()
    {
        return array_keys(self::TRANSLATION_OPTIONS);
    }

    /**
     * @param string $lang
     * @return bool
     */
    public static function isSupported($lang)
    {
        return in_array($lang, self::SUPPORTED, true);
    }

    /**
     * @param string $lang
     * @return bool
     */
    public static function isTranslationTarget($lang)
    {
        return isset(self::TRANSLATION_OPTIONS[$lang]);
    }
}