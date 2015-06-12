<?php
/**
 * Name: obs_language.php
 * Description: Provides support for correctly sorting a list of language names where each name is written it its own
 * script so it will be readable by those who can write in that language.
 *
 * Author: Phil Hopper
 * Date:   2014-12-10
 */
class ObsLanguage {

    /** @var string */
    public $isoCode;

    /** @var string */
    public $name;

    /** @var int */
    public $checkingLevel;

    /** @var Collator */
    public static $collator; // unicode collator for sorting language names correctly

    function __construct($isoCode, $name, $level=1) {

        $this->isoCode = $isoCode;
        $this->name = $name;
        $this->checkingLevel = $level;
    }

    /**
     * Used by ObsLanguage::sort to sort ObsLanguage objects by name rather than isoCode, which is the order received
     * from the server. Uses UCA rules found here: http://www.unicode.org/reports/tr10/
     * @param ObsLanguage $obj1
     * @param ObsLanguage $obj2
     * @return int
     */
    private static function compare($obj1, $obj2) {

// because we don't yet have intl support, don't try to sort
//
//        // do not try to sort objects that are not ObsLanguage
//        if ((get_class($obj1) != 'ObsLanguage') || (get_class($obj2) != 'ObsLanguage'))
//            return 0;
//
//        // it is the responsibility of the calling method to make sure self::$collator is initialized
//        return self::$collator->compare($obj1->name, $obj2->name);
    }

    public static function sort(&$array) {

// because we don't yet have intl support, don't try to sort
//
//        if (empty(self::$collator))
//            self::$collator = new Collator('root'); // use UCA rules
//
//        usort($array, function($obj1, $obj2) {
//                return self::compare($obj1, $obj2);
//            });
    }
}
