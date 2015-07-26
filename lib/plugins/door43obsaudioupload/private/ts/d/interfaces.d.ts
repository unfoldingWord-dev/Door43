/**
 * Name: interfaces.d.ts
 * Description: Interface definitions and misc global variables
 *
 * Author: Phil Hopper
 * Date:   2015-01-06
 */
interface BucketInfo {
    endPoint: string;
    accessKey: string;
}

interface LocalizedStrings {
    plugins: Object;
}

interface LanguageDetail {
    cc: string[];
    lc: string;
    ln: string;
    lr: string;
}

interface LanguageList {
    count: number;
    results: LanguageDetail[];
}

interface ObsFrame {
    id: string;
    img: string;
    text: string;
}

interface ObsChapter {
    frames: ObsFrame[];
    number: string; // '01'
    ref: string; // 'A Bible story from: Genesis 1-2'
    title: string; // '1. The Creation'
}

interface ObsChapterData {
    app_words: Object;
    chapters: ObsChapter[];
    date_modified: string; // '20141207'
    direction: string; // 'ltr', 'rtl'
    language: string; // 'en'
}

// these variables are defined by Dokuwiki
declare var DOKU_BASE: string;         // the full webserver path to the DokuWiki installation
declare var NS: string;                // $INFO['namespace'] passed through the function tpl_metaheaders()
//noinspection JSUnusedGlobalSymbols
declare var DOKU_TPL: string;          // the full webserver path to the used Template
declare var DOKU_COOKIE_PARAM: string; // parameters required to set similar cookies as in PHP:
                                       //    path – cookie path
                                       //    secure – whether secure cookie
declare var LANG: LocalizedStrings;    // an array of language strings
declare var JSINFO: string;            // an array of useful page info (see the section below)
