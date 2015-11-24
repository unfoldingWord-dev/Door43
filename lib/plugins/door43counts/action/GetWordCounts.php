<?php
/**
 * Name: GetWordCounts.php
 * Description:
 *
 * Author: Phil Hopper
 * Date:   11/18/15
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

// $door43shared is a global instance, and can be used by any of the door43 plugins
if (empty($door43shared)) {
    $door43shared = plugin_load('helper', 'door43shared');
}

/* @var $door43shared helper_plugin_door43shared */
$door43shared->loadActionBase();
$door43shared->loadAjaxHelper();

/**
 * Class action_plugin_door43counts_GetWordCounts
 */
class action_plugin_door43counts_GetWordCounts extends Door43_Action_Plugin {

    /**
     * @var int
     */
    private $bible_terms_count;

    /**
     * Registers a callback function for a given event
     *
     * @param Doku_Event_Handler $controller the DokuWiki event controller object
     * @return void
     */
    public function register(Doku_Event_Handler $controller) {
        Door43_Ajax_Helper::register_handler($controller, 'door43_word_counts', array($this, 'get_word_counts'));
    }

    public function get_word_counts() {

        // need a longer timeout
        set_time_limit(600);

        /* @var $door43shared helper_plugin_door43shared */
        global $door43shared;

        // $door43shared is a global instance, and can be used by any of the door43 plugins
        if (empty($door43shared)) {
            $door43shared = plugin_load('helper', 'door43shared');
        }

        // first check the cache
        /* @var $cache door43Cache */
        $cache = $door43shared->getCache();
        $cacheFile = 'word-counts.json';
        $return_val = $cache->getString($cacheFile);

        if (!empty($return_val)) {
            echo $return_val;
            return;
        }

        // get the catalog
        $url = "https://api.unfoldingword.org/ts/txt/2/catalog.json";
        $raw = file_get_contents($url);
        $catalog = json_decode($raw, true);

        $obs_counts = null;
        $bible_counts = array();

        foreach ($catalog as $item) {
            if ($item['slug'] == 'obs') {
                $obs_counts = $this->get_obs_counts($item['lang_catalog']);
            }
            else {
                $bible_counts[(int)$item['sort']] = array($item['slug'], $this->get_bible_counts($item['lang_catalog']));
            }
        }

        // get translation academy
        $ta_count = $this->get_ta_count('https://api.unfoldingword.org/ta/txt/1/en/ta-en.json');

        $return_val = json_encode(array(
            'terms' => $this->bible_terms_count,
            'obs' => $obs_counts,
            'bible' => $bible_counts,
            'ta' => $ta_count
        ));

        $cache->saveString($cacheFile, $return_val);
        echo $return_val;
    }

    /**
     * Returns the word counts for OBS items in the catalog
     * @param string $obs_catalog_url
     * @return array
     */
    private function get_obs_counts($obs_catalog_url) {

        $return_val = array();

        // get obs languages
        $raw = file_get_contents($obs_catalog_url);
        $langs = json_decode($raw, true);

        // we just want english
        $langs = array_filter($langs, function($v) {
            return $v['language']['slug'] == 'en';
        });

        if (empty($langs)) return $return_val;

        // get the list of resources
        $lang = array_shift($langs);
        $url = $lang['res_catalog'];
        $raw = file_get_contents($url);
        $resources = json_decode($raw, true);

        if (empty($resources)) return $return_val;
        $resource = $resources[0];

        // get source word count
        $source_count = 0;
        $url = $resource['source'];
        $raw = file_get_contents($url);
        $source = json_decode($raw, true);

        if (!empty($source)) {
            foreach ($source['chapters'] as $chapter) {
                foreach ($chapter['frames'] as $frame) {
                    $source_count += str_word_count($frame['text']);
                }
            }
            $return_val[] = array('OBS', $source_count);
        }

        // get notes word count
        $note_count = 0;
        $url = $resource['notes'];
        $raw = file_get_contents($url);
        $notes = json_decode($raw, true);

        if (!empty($notes)) {
            foreach ($notes as $note) {
                if (!empty($note['tn']) && is_array($note['tn'])) {
                    foreach ($note['tn'] as $tn) {
                        $note_count += str_word_count($tn['text']);
                        $note_count += str_word_count($tn['ref']);
                    }
                }
            }
            $return_val[] = array('OBS tN', $note_count);
        }

        // get terms word count
        $term_count = 0;
        $url = $resource['terms'];
        $raw = file_get_contents($url);
        $terms = json_decode($raw, true);

        if (!empty($terms)) {
            foreach ($terms as $term) {
                $term_count += str_word_count($term['term']);
                $term_count += str_word_count($term['def']);

                if (!empty($term['ex']) && is_array($term['ex'])) {
                    foreach ($term['ex'] as $ex) {
                        $term_count += str_word_count($ex['text']);
                    }
                }
            }
            $return_val[] = array('OBS tW', $term_count);
        }

        // get checking_questions wor count
        $cq_count = 0;
        $url = $resource['checking_questions'];
        $raw = file_get_contents($url);
        $questions = json_decode($raw, true);

        if (!empty($questions)) {
            foreach ($questions as $question) {

                if (empty($question['cq'])) continue;

                foreach ($question['cq'] as $cq) {
                    $cq_count += str_word_count($cq['q']);
                    $cq_count += str_word_count($cq['a']);
                }
            }
            $return_val[] = array('OBS tQ', $cq_count);
        }

        return $return_val;
    }

    /**
     * Returns word counts for ULB and UDB items
     * @param string $book_catalog_url
     * @return array
     */
    private function get_bible_counts($book_catalog_url) {

        $return_val = array('ulb' => array(), 'udb' => array());
        $notes_count = 0;
        $cq_count = 0;

        // get bible languages
        $raw = file_get_contents($book_catalog_url);
        $langs = json_decode($raw, true);

        // we just want english
        $langs = array_filter($langs, function($v) {
            return $v['language']['slug'] == 'en';
        });

        if (empty($langs)) return $return_val;

        // get the list of resources
        $lang = array_shift($langs);
        $url = $lang['res_catalog'];
        $raw = file_get_contents($url);
        $resources = json_decode($raw, true);

        // loop through the resources
        foreach ($resources as $resource) {

            // get the term word count
            if (empty($this->bible_terms_count) && !empty($resource['terms'])) {

                $term_count = 0;
                $url = $resource['terms'];
                $raw = file_get_contents($url);

                $terms = json_decode($raw, true);

                if (!empty($terms)) {
                    foreach ($terms as $term) {
                        $term_count += str_word_count($term['term']);
                        $term_count += str_word_count($term['def']);

                        if (!empty($term['ex']) && is_array($term['ex'])) {
                            foreach ($term['ex'] as $ex) {
                                $term_count += str_word_count($ex['text']);
                            }
                        }
                    }
                    $this->bible_terms_count = $term_count;
                }
            }

            // get source word count
            $url = $resource['usfm'];
            $usfm = file_get_contents($url);

            // remove format markers
            $usfm = str_replace("\n\n", "\n", $usfm);
            $usfm = str_replace("\n\n\n", "\n", $usfm);
            $usfm = preg_replace('/\\\\[v]\s\d+\s/', '', $usfm);
            $usfm = preg_replace('/\\\\[c]\s\d+\s/', '', $usfm);
            $usfm = preg_replace('/\\\\\S+\s/', '', $usfm);
            $return_val[$resource['slug']] = str_word_count($usfm);

            // get note word count, just once
            if (empty($notes_count) && !empty($resource['notes'])) {
                $url = $resource['notes'];
                $raw = file_get_contents($url);
                $notes = json_decode($raw, true);

                foreach ($notes as $note) {
                    if (!empty($note['tn'])) {

                        foreach ($note['tn'] as $tn) {
                            $notes_count += str_word_count($tn['ref']);
                            $notes_count += str_word_count($tn['text']);
                        }
                    }
                }
            }

            // get checking question count, just once
            if (empty($cq_count) && !empty($resource['checking_questions'])) {
                $url = $resource['checking_questions'];
                $raw = file_get_contents($url);
                $questions = json_decode($raw, true);

                if (!empty($questions)) {
                    foreach ($questions as $question) {

                        if (empty($question['cq'])) continue;

                        foreach ($question['cq'] as $cq) {
                            $cq_count += str_word_count($cq['q']);
                            $cq_count += str_word_count($cq['a']);
                        }
                    }
                }
            }
        }

        $return_val['notes'] = $notes_count;
        $return_val['questions'] = $cq_count;

        return $return_val;
    }

    /**
     * Returns the word caunt for translation academy
     * @param string $ta_endpoint_url
     * @return int
     */
    private function get_ta_count($ta_endpoint_url) {

        // get translation academy
        $ta_count = 0;
        $raw = file_get_contents($ta_endpoint_url);
        $ta = json_decode($raw, true);

        foreach($ta['chapters'] as $chapter) {
            if (!empty($chapter['title'])) {
                $ta_count += str_word_count($chapter['title']);
            }
            $ta_count += $this->process_ta_block($chapter);
        }

        return $ta_count;
    }

    /**
     * Each block in translation academy can have sub-blocks
     * @param $block
     * @return int
     */
    private function process_ta_block($block) {

        $return_val = 0;

        //loop through frames
        if (!empty($block['frames'])) {
            foreach($block['frames'] as $frame) {

                if (!empty($frame['text'])) {
                    $text = html_entity_decode(strip_tags($frame['text']));
                    $return_val += str_word_count($text);
                }

                if (!empty($frame['title'])) {
                    $return_val += str_word_count($frame['title']);
                }

                $return_val += $this->process_ta_block($frame);
            }
        }

        // loop through sections
        if (!empty($block['sections'])) {
            foreach ($block['sections'] as $section) {

                if (!empty($section['title'])) {
                    $return_val += str_word_count($section['title']);
                }

                $return_val += $this->process_ta_block($section);
            }
        }

        return $return_val;
    }
}
