<?php
/**
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR | E_WARNING | E_PARSE);

// must be run within Dokuwiki
if (!defined('DOKU_INC')) {
    die();
}

if (!defined('DOKU_PLUGIN')) {
    define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
}
require_once(DOKU_PLUGIN . 'action.php');


if (!function_exists('file_get_json')) {
    function file_get_json($file) {
        return json_decode(@file_get_contents($file), true);
    }
}
if (!function_exists('file_put_json')) {
    function file_put_json($file, $array) {
        $json = json_encode($array, JSON_PRETTY_PRINT);
        if ($json == 'null') {
            $json = '[]';
        }
        file_put_contents($file, $json);
    }
}

class action_plugin_door43gitmerge extends DokuWiki_Action_Plugin {

    function register(Doku_Event_Handler $controller) {
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, '_ajax_call');
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'handle_tpl_act', array());
        $controller->register_hook('TPL_ACT_RENDER', 'BEFORE', $this, 'compile_merge_data', array());
        $controller->register_hook('TPL_CONTENT_DISPLAY', 'BEFORE', $this, 'render_merge_interface', array());
        $controller->register_hook('TEMPLATE_PAGETOOLS_DISPLAY', 'BEFORE', $this, 'handle_add_merge_button');
    }

    function _ajax_call(&$event, $param) {
        global $INPUT, $ID;

        if ($event->data !== 'door43gitmerge') {
            return;
        }
        $event->stopPropagation();
        $event->preventDefault();
        $ID = $INPUT->post->str('page');
        $action = 'none';

        switch ($INPUT->post->str('action')) {
            case 'dismiss':
                $this->_dismiss($INPUT->post->str('device'), $INPUT->post->str('frame'));
                $action = 'dismiss';
                break;
            case 'apply':
                $content = $this->_apply($INPUT->post->str('device'), $INPUT->post->str('frame'));
                $action = 'apply';
                break;
            case 'apply-edited':
                $content = $this->_apply_edited($INPUT->post->str('device'), $INPUT->post->str('frame'), $INPUT->post->str('content'));
                $action = 'apply-edited';
                break;
            case 'mark-updated':
                $content = $this->_mark_updated($INPUT->post->str('lang'), $INPUT->post->str('project'), $INPUT->post->str('device'), $INPUT->post->str('files'));
                $action = 'mark-updated';
                break;
            default:
                break;
        }
        $return = array(
            'action' => $action,
            'status' => 1
        );
        if (!empty($content)) {
            $return['content'] = $content;
        }
        echo json_encode($return);

    }

    function handle_tpl_act(&$event, $param) {
        global $INPUT, $ID;

        $do = $event->data;
        if (is_array($do)) {
            list($do) = array_keys($do);
        }

        switch ($do) {
            case 'door43gitmerge':
                $this->show_merge_interface = true;
                $event->data = 'show';
                break;
            case 'door43gitmerge-dismiss':
                $this->_dismiss($INPUT->get->str('device'), $INPUT->get->str('frame'));
                header('Location: /' . str_replace(':', '/', $ID));
                exit;
                break;
            case 'door43gitmerge-apply':
                $this->_apply($INPUT->get->str('device'), $INPUT->get->str('frame'));
                header('Location: /' . str_replace(':', '/', $ID));
                exit;
                break;
            case 'door43gitmerge-apply-edited':
                $this->_apply_edited($INPUT->get->str('device'), $INPUT->get->str('frame'), $INPUT->get->str('content'));
                header('Location: /' . str_replace(':', '/', $ID));
                exit;
                break;
            case 'door43gitmerge-crawl':
                $this->_crawl();
                header('Location: /' . str_replace(':', '/', $ID));
                exit;
                break;
            case 'door43gitmerge-reset':
                $this->_reset();
                header('Location: /' . str_replace(':', '/', $ID));
                exit;
                break;
            default:
                break;
        }
    }

    private function _debug($var) {
        echo '<pre>';
        print_r($var);
        echo '</pre>';
    }

    private function _init() {
        global $ID, $conf;

        if ($this->ready) {
            return;
        }
        $this->ready = 1;
        list($this->lang, $this->project, $this->id) = explode(':', $ID);
        $this->repo_path = $this->getConf('repo_path') . '/';
        $this->project_dir = 'uw-' . $this->project . '-' . $this->lang . '/';
        $this->page_path = $this->project_dir . $this->id . '/';
        $this->cache_path = $conf['cachedir'] . '/door43gitmerge/';
        if (!is_dir($this->cache_path)) {
            mkdir($this->cache_path, 0775, 1);
        }
        $this->merge_updated_file = $this->_get_log_filename($this->project, $this->lang, $this->id, 'updated');
        $this->merge_log_file = $this->_get_log_filename($this->project, $this->lang, $this->id, 'log');

        // check about continuing
        $projects = array('obs');
        $this->on = in_array($this->project, $projects) && $this->id != '' && $this->id == preg_replace('/[^0-9]*/', '', $this->id);
        unset($projects);

        if (!$this->on) {
            return;
        }

        // get count for badge
        $merge_updated_data = file_get_json($this->cache_path . $this->merge_updated_file);
        $this->updated_frames = $merge_updated_data['frames'];
        $this->updated_frame_count = count($this->updated_frames);
        unset($merge_updated_data);
    }

    private function _load_existing_frames() {
        global $ID;

        //load source
        $source = preg_replace('/(?:[\r\n]*)(\{\{[^\}]*\}\})(?:[\r\n]*)/', '<!-- Frame -->$1<!-- Image -->', rawWiki($ID));
        $source = trim($source . '');
        $source = preg_replace('/(?:[\r\n]+)(\/\/[^\/]*\/\/)/', '<!-- Reference -->$1', $source);
        list($source, $reference) = explode('<!-- Reference -->', $source);
        $frames = explode('<!-- Frame -->', $source);
        unset($source);

        //set data
        $this->title = preg_replace('/^(?:\s*======\s*)?(.*?)(?:\s*======\s*)?$/', '$1', array_shift($frames));
        $this->reference = preg_replace('/^(?:\s*\/\/\s*)?(.*?)(?:\s*\/\/\s*)?$/', '$1', $reference);
        unset($reference);
        $frame_keys = array();
        for ($i = 1; $i <= count($frames); $i++) {
            array_push($frame_keys, str_pad($i, 2, '0', STR_PAD_LEFT));
        }
        unset($i);
        $this->frames = array_combine($frame_keys, $frames);
        unset($frames, $frame_keys);

    }

    private function _update_log($device, $frame, $action, $time = 'default', $merge_updated_file = 'default', $merge_log_file = 'default') {

        //set variables
        if ($time == 'default') {
            $time = date('Y-m-d H:i:s');
        }
        if ($merge_updated_file == 'default') {
            $merge_updated_file = $this->merge_updated_file;
        }
        if ($merge_log_file == 'default') {
            $merge_log_file = $this->merge_log_file;
        }

        //load updated json
        $array = file_get_json($this->cache_path . $merge_updated_file);

        //if update
        if ($action == 'updated') {

            //add frame to device
            $array['devices'][$device][$frame] = $time;

            //add device to frame
            $frame_exists = 0;
            if ($array['frames'][$frame]) {
                foreach ($array['frames'][$frame] as $key => $this_device) {
                    if ($device == $this_device) {
                        $frame_exists = 1;
                    }
                }
            }
            if ($frame_exists == 0) {
                $array['frames'][$frame][] = $device;
            }

        } //if apply or dismiss
        else {

            //remove frame from device
            unset($array['devices'][$device][$frame]);

            //remove device if empty
            if (!count($array['devices'][$device])) {
                unset($array['devices'][$device]);
            }

            //remove device from frame
            if ($array['frames'][$frame]) {
                foreach ($array['frames'][$frame] as $key => $this_device) {
                    if ($device == $this_device) {
                        unset($array['frames'][$frame][$key]);
                    }
                }
            }

            //remove frame if empty
            if (!count($array['frames'][$frame])) {
                unset($array['frames'][$frame]);
            }

        }

        //save updated json
        file_put_json($this->cache_path . $merge_updated_file, $array);

        //load log json
        $array = file_get_json($this->cache_path . $merge_log_file);

        //update log
        $array[$device][$frame] = array(
            'time' => $time,
            'action' => $action
        );

        //save log json
        file_put_json($this->cache_path . $merge_log_file, $array);

    }

    private function _get_log_filename($project, $lang, $id, $type) {

        return $project . '-' . $lang . '-' . $id . '.' . $type . '.json';

    }

    private function _dismiss($device, $frame) {
        $this->_init();

        //update json
        $this->_update_log($device, $frame, 'dismissed');
    }

    private function _apply($device, $frame) {
        global $ID;
        $this->_init();
        $this->_load_existing_frames();

        //replace frame with new content
        $new_content = $this->_content($device, $frame);
        $frames = $this->frames;
        $title = ($frame == 'title') ? $new_content : $this->title;
        $reference = ($frame == 'reference') ? $new_content : $this->reference;
        if ($frame != 'title' && $frame != 'reference') {
            list($image) = explode('<!-- Image -->', $frames[$frame]);
            $frames[$frame] = $image . '<!-- Image -->' . $new_content;
        }
        $new_wikitext = str_replace('<!-- Image -->', "\n\n", '====== ' . $title . ' ======' . "\n\n\n\n" . implode("\n\n\n\n", $frames) . "\n\n\n\n" . '// ' . $reference . ' //');

        //save source
        saveWikiText($ID, $new_wikitext, 'Updated frame ' . $frame . ' to new version from device ' . $device . '.');

        //update json
        $this->_update_log($device, $frame, 'applied');

        //return content
        if ($frame == 'title') {
            $new_content = '== ' . $new_content . ' ==';
        }
        if ($frame == 'reference') {
            $new_content = '// ' . $new_content . ' //';
        }
        return p_render('xhtml', p_get_instructions($new_content), $info);
    }

    private function _apply_edited($device, $frame, $new_content) {
        global $ID;
        $this->_init();
        $this->_load_existing_frames();

        //replace frame with new content
        $frames = $this->frames;
        $title = ($frame == 'title') ? $new_content : $this->title;
        $reference = ($frame == 'reference') ? $new_content : $this->reference;
        if ($frame != 'title' && $frame != 'reference') {
            list($image) = explode('<!-- Image -->', $frames[$frame]);
            $frames[$frame] = $image . '<!-- Image -->' . $new_content;
        }
        $new_wikitext = str_replace('<!-- Image -->', "\n\n", '====== ' . $title . ' ======' . "\n\n\n\n" . implode("\n\n\n\n", $frames) . "\n\n\n\n" . '// ' . $reference . ' //');

        //save source
        saveWikiText($ID, $new_wikitext, 'Updated frame ' . $frame . ' to revision of version from device ' . $device . '.');

        //update json
        $this->_update_log($device, $frame, 'applied');

        //return content
        if ($frame == 'title') {
            $new_content = '== ' . $new_content . ' ==';
        }
        if ($frame == 'reference') {
            $new_content = '// ' . $new_content . ' //';
        }
        return p_render('xhtml', p_get_instructions($new_content), $info);
    }

    private function _user($device) {
        $json =  file_get_json($this->repo_path . $device . '/profile/contact.json');
        if($json === null) {
            $json = array(
                'name'=>$device
            );
        }
        return $json;
    }

    private function _content($device, $frame) {
        return trim(file_get_contents($this->repo_path . $device . '/' . $this->page_path . $frame . '.txt') . '');
    }

    private function _crawl() {
        $this->_init();

        //preload existing json
        $files = array();
        if (is_dir($this->cache_path)) {
            $jsons = scandir($this->cache_path);
            foreach ($jsons as $json_filename) {
                if (substr($json_filename, -5) != '.json') {
                    continue;
                }
                list($file, $type) = explode('.', substr($json_filename, 0, -5));
                if ($type != 'updated' && $type != 'log') {
                    continue;
                }
                $file_parts = explode('-', $file);
                $project = array_shift($file_parts);
                $id = array_pop($file_parts);
                $lang = implode('-', $file_parts);
                //list($project, $lang, $id) = explode('-', $file);
                $files[$project][$lang][$id][$type] = file_get_json($this->cache_path . $json_filename);
                unset($file, $type, $project, $lang, $id);
            }
        }
        unset($jsons, $json_filename);

        //crawl repos and build update
        $devices_path = $this->repo_path;
        $devices = @scandir($devices_path);
        if (!empty($devices)) {
            foreach ($devices as $device_index => $device) {
                if (substr($device, 0, 1) == '.' || !is_dir($devices_path . $device . '/')) {
                    unset($devices[$device_index]);
                    continue;
                }
                $projects_path = $devices_path . $device . '/';
                $projects = scandir($projects_path);
                if (!empty($projects)) {
                    foreach ($projects as $project_index => $project_filename) {
                        if (substr($project_filename, 0, 3) != 'uw-' || !is_dir($projects_path . $project_filename . '/')) {
                            unset($projects[$project_index]);
                            continue;
                        }
                        unset($file_parts);
                        $file_parts = explode('-', substr($project_filename, 3));
                        $project = array_shift($file_parts);
                        $lang = implode('-', $file_parts);
                        //list($project, $lang) = explode('-', substr($project_filename, 3));
                        $ids_path = $projects_path . $project_filename . '/';
                        $ids = scandir($ids_path);
                        if (!empty($ids)) {
                            foreach ($ids as $id_index => $id) {
                                if (substr($id, 0, 1) == '.' || !is_dir($ids_path . $id . '/')) {
                                    unset($ids[$id_index]);
                                    continue;
                                }
                                $data = &$files[$project][$lang][$id];
                                $frames_path = $ids_path . $id . '/';
                                $frames = scandir($frames_path);
                                if (!empty($frames)) {
                                    foreach ($frames as $frame_index => $frame_filename) {
                                        if (substr($frame_filename, -4) != '.txt') {
                                            unset($frames[$frame_index]);
                                            continue;
                                        }
                                        $frame = substr($frame_filename, 0, -4);
                                        $updated = date('Y-m-d H:i:s', filemtime($frames_path . $frame_filename));
                                        if ($data['log'][$device][$frame]['time'] < $updated) {
                                            $data['updated']['devices'][$device][$frame] = $updated;
                                            $data['updated']['frames'][$frame][] = $device;
                                            $data['log'][$device][$frame] = array(
                                                'time' => $updated,
                                                'action' => 'updated'
                                            );
                                        }
                                    }
                                }
                                unset($frame_index, $frame_filename, $frame, $updated);
                            }
                        }
                        unset($id_index, $id);
                    }
                }
                unset($project_index, $project);
            }
        }
        unset($device_index, $device);

        //update json
        foreach ($files as $project => $langs) {
            foreach ($langs as $lang => $ids) {
                foreach ($ids as $id => $content) {
                    file_put_json($this->cache_path . $this->_get_log_filename($project, $lang, $id, 'updated'), $content['updated']);
                    file_put_json($this->cache_path . $this->_get_log_filename($project, $lang, $id, 'log'), $content['log']);
                }
            }
        }
        unset($files, $project, $langs, $lang, $ids, $id, $content);

    }

    private function _reset() {
        $this->_init();

        //delete existing json
        if (is_dir($this->cache_path)) {
            $jsons = scandir($this->cache_path);
            foreach ($jsons as $json_filename) {
                if (substr($json_filename, -5) != '.json') {
                    continue;
                }
                list($file, $type) = explode('.', substr($json_filename, 0, -5));
                if ($type != 'updated' && $type != 'log') {
                    continue;
                }
                unlink($this->cache_path . $json_filename);
                unset($file, $type);
            }
        }
        $this->_crawl();

    }

    private function _mark_updated($lang, $project, $device, $files) {
        $this->_init();
        $ready_file = $this->repo_path . $device . '/uw-' . $project . '-' . $lang . '/READY';
        // NOTE: the hooks peform this check but we do it again for redundancy
        if (file_exists($ready_file)) {
            // parse file list
            $files = explode(',', trim($files));
            $chapters = [];
            if (!empty($files)) {
                foreach ($files as $file) {
                    list($chapter, $frame) = explode('-', trim($file));
                    if(isset($chapter) && isset($frame)) {
                        $chapters[$chapter][] = $frame;
                    }
                }
            }
            unset($ready_file, $files, $file, $frame);

            // update json
            if (!empty($chapters)) {
                foreach ($chapters as $chapter => $frames) {
                    $merge_updated_file = $this->_get_log_filename($project, $lang, $chapter, 'updated');
                    $merge_log_file = $this->_get_log_filename($project, $lang, $chapter, 'log');
                    if(!empty($frames)) {
                        foreach ($frames as $frame) {
                            $time = date('Y-m-d H:i:s', filemtime($this->repo_path . $device . '/uw-' . $project . '-' . $lang . '/' . $chapter . '/' . $frame . '.txt'));
                            $this->_update_log($device, $frame, 'updated', $time, $merge_updated_file, $merge_log_file);
                        }
                        unset($frame, $time);
                    }
                    unset($merge_updated_file, $merge_log_file);
                }
            }
            unset($chapters, $chapter);
        }
    }

    function compile_merge_data(&$event, $param) {
        global $ID, $INFO;

        $this->_init();

        if ($event->data != 'show' || !$this->show_merge_interface) {
            return;
        } // nothing to do for us

    }

    function render_merge_interface(&$event, $param) {

        if ($this->show_merge_interface) {

            $this->_load_existing_frames();

            if (!empty($this->updated_frames)) {
                foreach($this->updated_frames as $frame=>$devices) {
                    foreach($devices as $device) {
                        if (empty($this->devices[$device])) {
                            $this->devices[$device] = $this->_user($device);
                        }
                    }
                }
                @asort($this->devices);
                @reset($this->devices);

                if (AUTH_ADMIN) {
                    echo '<form id="door43gitmerge-power-controls">';
                    echo '<div class="chapter-version-selection">';
                    echo $this->getLang('show').': ';
                    echo '<select class="door43gitmerge-diff-switcher-all">';
                    echo '<option value="all" selected="selected">'.$this->getLang('all').'</option>';
                    foreach ($this->devices as $device=>$user) {
                        echo '<option value="'.$device.'">'.$user['name'].'</option>';
                    }
                    unset($device, $user);
                    echo '</select>';
                    echo '</div>';
                    echo '<div class="door43gitmerge-actions actions-all">';
                    echo '<input type="button" id="door43gitmerge-dismiss-all-from-device" value="'.$this->getLang('dismiss_all').'"> ';
                    echo '<input type="button" id="door43gitmerge-apply-all-from-device" value="'.$this->getLang('apply_all').'">';
                    echo '</div>';
                    echo '</form>';
                }
            }

            echo p_render('xhtml', p_get_instructions('====== ' . $this->title . ' ======'), $info);

            //loop through frames with available merge options
            if (!empty($this->updated_frames['title'])) {
                $this->render_merge_interface_frame('title', $this->updated_frames['title']);
                unset($this->updated_frames['title']);
            }
            if (!empty($this->updated_frames)) {
                foreach ($this->updated_frames as $frame => $data_array) {
                    $this->render_merge_interface_frame($frame, $data_array);
                }
?>
<script type="text/javascript">/*<![CDATA[*/
jQuery(document).on('change input', '.door43gitmerge-diff-switcher-all', function(){
  var elem = jQuery(this)
    , device = elem.val()
    , selects = jQuery('.door43gitmerge-diff-switcher')
    , powerControlButtonContainer = jQuery('#door43gitmerge-power-controls .actions-all');
  if (device=='all') {
    selects.each(function(i){
      jQuery(this).children().removeAttr('selected').first().attr('selected', 'selected');
    });
    powerControlButtonContainer.addClass('no-available-options');
  }
  else {
    selects.children().removeAttr('selected').filter('[value="'+device+'"]').attr('selected', 'selected');
    powerControlButtonContainer.removeClass('no-available-options');
  }
  jQuery(selects).trigger('change');
});
jQuery(document).on('change input', '.door43gitmerge-diff-switcher', function(){
  var elem = jQuery(this)
    , frameContainer = elem.parents('.frame')
    , lastDevice = elem.attr('data-last-value')
    , device = elem.val()
    , selectedItem = elem.children().filter('[selected]').attr('value')
    , frame = elem.attr('data-frame')
    , diffs = jQuery('#frame-'+frame+' .table');
  if (typeof selectedItem==='undefined') {
    frameContainer.addClass('no-available-options');
    return;
  }
  frameContainer.removeClass('no-available-options');
  if (lastDevice==device) return;
  lastDevice = device;
  if (device=='all') diffs.addClass('show');
  else diffs.removeClass('show').filter('[data-device="'+device+'"]').addClass('show');
});
jQuery(document).on('click', '.door43gitmerge-actions input[type="submit"]', function(e){
  e.preventDefault();
  var elem = jQuery(this)
    , page = elem.attr('data-page')
    , frame = elem.attr('data-frame')
    , device = elem.attr('data-device')
    , action = elem.attr('data-action')
    , frameElem = jQuery('#frame-'+frame)
    , contentElem = frameElem.find('.frame-content')
    , inputElems = frameElem.find('input[type="submit"], input[type="button"], input[type="reset"], textarea, select')
    , contentElem = frameElem.find('.door43gitmerge-content')
    , selectElem = frameElem.find('.door43gitmerge-diff-switcher');
  frameElem.addClass('disabled');
  inputElems.attr('disabled', 'disabled');
  jQuery.post(
    DOKU_BASE + 'lib/exe/ajax.php',
    {
      call: 'door43gitmerge',
      action: action,
      page: page,
      frame: frame,
      device: device,
      content: contentElem.val()
    },
    function(data) {
      if (data.status) {
        inputElems.removeAttr('disabled');
        frameElem.removeClass('disabled').find('.table[data-device="'+device+'"]').remove();
        if (data.action=='apply' || data.action=='apply-edited') contentElem.html(data.content);
        selectElem.children('option[value="'+device+'"]').remove();
        selectElem.trigger('change');
        if (!frameElem.find('.table').length) setTimeout(function(){
          frameElem.remove();
          if (!jQuery('.frame').length) {
            jQuery('.page.group .level1').html('All available merges have been managed. <a href="?">Return to Page</a>');
            window.scrollTo(0,0);
          }
        }, 100);
      }
    },
    'json'
  );
});
jQuery(document).on('click', '.door43gitmerge-edit', function(e){
  e.preventDefault();
  var elem = jQuery(this)
    , frame = elem.attr('data-frame')
    , device = elem.attr('data-device')
    , formElem = jQuery('.table[data-frame="'+frame+'"][data-device="'+device+'"] form');
  formElem.addClass('mode-edit');
});
jQuery(document).on('click', '.door43gitmerge-cancel', function(e){
  e.preventDefault();
  var elem = jQuery(this)
    , frame = elem.attr('data-frame')
    , device = elem.attr('data-device')
    , formElem = jQuery('.table[data-frame="'+frame+'"][data-device="'+device+'"] form');
  formElem[0].reset();
  formElem.removeClass('mode-edit');
});
jQuery(document).on('click', '#door43gitmerge-dismiss-all-from-device', function(e){
  e.preventDefault();
  var availableFrames = jQuery('.frame:not(.no-available-options)')
    , availableFramesDismissButtons = availableFrames.find('.table.show .door43gitmerge-dismiss');
  availableFramesDismissButtons.trigger('click');
});
jQuery(document).on('click', '#door43gitmerge-apply-all-from-device', function(e){
  e.preventDefault();
  var availableFrames = jQuery('.frame:not(.no-available-options)')
    , availableFramesApplyButtons = availableFrames.find('.table.show .door43gitmerge-apply');
  availableFramesApplyButtons.trigger('click');
});
jQuery(document).on('ready', function(){
  var elem = jQuery('.door43gitmerge-diff-switcher-all');
  elem.trigger('change');
});
/*!]]>*/</script>
<?php
            } else {
                echo 'All available merges have been managed. <a href="?">Return to Page</a>';
            }
            $event->preventDefault();
        }
    }

    private function render_merge_interface_frame($frame, $data_array) {
        if ($frame == 'title') {
            $current_content = $this->title;
        } elseif ($frame == 'reference') {
            $current_content = $this->reference;
        } else {
            list($image, $current_content) = explode('<!-- Image -->', $this->frames[$frame]);
        }
        $current_content = trim($current_content);

        //remove device frames that match current frames
        foreach ($data_array as $index => $device) {
            $new_content = $this->_content($device, $frame);
            if (preg_replace('/[\r\n]*/', ' ', $current_content) == preg_replace('/[\r\n]*/', ' ', $new_content)) {
                $this->_dismiss($device, $frame);
                unset($data_array[$index]);
            }
        }
        unset($index, $device);
        if (!count($data_array)) {
            return;
        }
        foreach ($data_array as $device) $user_array[$device] = $this->devices[$device];
        @asort($user_array);
        @reset($user_array);

        echo '<div id="frame-' . $frame . '" class="frame">';
        if ($frame == 'title' || $frame == 'reference') {
            $frame_title = ucwords($frame);
        } else {
            $frame_title = 'Frame ' . $frame;
        }
        echo p_render('xhtml', p_get_instructions('===' . $frame_title . '==='), $info);
        unset($frame_title);
        echo '<div class="frame-content-container">';
        if (!empty($image)) {
            echo p_render('xhtml', p_get_instructions($image), $info);
        }
        unset($image);
        echo '<div class="frame-content">';
        if ($frame == 'title') {
            echo p_render('xhtml', p_get_instructions('== ' . $current_content . ' =='), $info);
        } elseif ($frame == 'reference') {
            echo p_render('xhtml', p_get_instructions('// ' . $current_content . ' //'), $info);
        } else {
            echo p_render('xhtml', p_get_instructions($current_content), $info);
        }
        echo '</div>';
        echo '</div>';
        echo '<div class="frame-version-selection">';
        echo $this->getLang('version_to_compare') . ': ';
        echo '<select class="door43gitmerge-diff-switcher" data-frame="' . $frame . '">';
        if (is_array($user_array) && count($user_array)) {
            foreach ($user_array as $device=>$user) {
                if (!isset($first_device)) {
                    $first_device = $device;
                }
                echo '<option value="'.$device.'">'.$user['name'].'</option>';
            }
        }
        echo '<option value="all">' . $this->getLang('show_all') . '</option>';
        unset($device, $user);
        echo '</select>';
        echo '</div>';
        echo '<div class="frame-diffs">';
        if (is_array($user_array) && count($user_array)) {
            foreach ($user_array as $device => $user) {
                $new_content = $this->_content($device, $frame);
                $this->html_diff($frame, $device, $current_content, $new_content, $device==$first_device);
            }
        }
        unset($device, $user, $new_content, $first_device);
        echo '</div>';
        echo '</div>';
    }

    public function handle_add_merge_button(&$event, $param) {
        global $ID, $INPUT, $REV, $INFO;

        $do = $event->data;
        if (is_array($do)) {
            list($do) = array_keys($do);
        }

        if (!$this->on || ($this->on && strpos($do, 'door43gitmerge') === false && strpos($INPUT->get->str('do'), 'door43gitmerge') === false && $INPUT->get->str('do') !== '')) {
            $this->on = 0;
            return;
        }

        if ($this->show_merge_interface) {
            echo '<li><a href="' . wl($INFO['id']) . '" class="action show" accesskey="v" rel="nofollow" title="' . $this->getLang('back') . ' [V]"><span>' . $this->getLang('back') . '</span></a></li>';
        } else {
            $badge = $this->updated_frame_count > 0 ? $this->updated_frame_count : '';
            echo '<li data-badge="' . $badge . '"><a class="action merge" title="' . $this->getLang('merge') . '" href="' . wl($INFO['id']) . '?do=door43gitmerge"><span>' . $this->getLang('merge') . '</span></a></li>';
        }
    }

    private function html_diff($frame, $device, $l_text = '', $r_text = '', $show = 1) {
        global $ID, $REV, $lang, $INPUT, $INFO;

        /*
         * Determine diff type
         */
        if ($INFO['ismobile']) {
            $type = 'inline';
        } else {
            $type = 'sidebyside';
        }

        /*
         * Create diff object and the formatter
         */
        require_once(DOKU_INC . 'inc/DifferenceEngine.php');
        $diff = new Diff(explode("\n", $l_text), explode("\n", $r_text));

        if ($type == 'inline') {
            $diffformatter = new InlineDiffFormatter();
        } else {
            $diffformatter = new TableDiffFormatter();
        }

        if ($show == 1) {
            $class = ' show';
        }

        /*
         * Display diff view table
         */
        ?>
        <div class="table frame-<?php echo $frame; ?>-diff<?php echo $class; ?>" data-frame="<?php echo $frame; ?>"
             data-device="<?php echo $device; ?>">
            <table class="diff diff_<?php echo $type ?>">

                <?php
                //navigation and header
                if ($type == 'inline') { ?>
                    <tr>
                        <td class="diff-lineheader">-</td>
                        <td>Current Version</td>
                    </tr>
                    <tr>
                        <th class="diff-lineheader">+</th>
                        <th><?php echo $this->devices[$device]['name']; ?>'s Version</th>
                    </tr>
                <?php } else { ?>
                    <tr>
                        <th colspan="2">Current Version</th>
                        <th colspan="2"><?php echo $this->devices[$device]['name']; ?>'s Version</th>
                    </tr>
                <?php }

                //diff view
                echo html_insert_softbreaks($diffformatter->format($diff)); ?>

            </table>
            <form class="mode-action">
                <input type="hidden" name="frame" value="<?php echo $frame; ?>">
                <input type="hidden" name="device" value="<?php echo $device; ?>">
                <textarea name="content" class="door43gitmerge-content" rows="5"
                          cols="50"><?php echo htmlspecialchars($r_text); ?></textarea>

                <div class="door43gitmerge-actions">
                <span class="door43gitmerge-action-interface">
                    <input name="do[door43gitmerge-dismiss]" type="submit" class="door43gitmerge-dismiss"
                           value="<?php echo $this->getLang('dismiss'); ?>" data-page="<?php echo $ID; ?>"
                           data-frame="<?php echo $frame; ?>" data-device="<?php echo $device; ?>"
                           data-action="dismiss">
                    <input type="button" class="door43gitmerge-edit"
                           value="<?php echo $this->getLang('edit_and_apply'); ?>" data-frame="<?php echo $frame; ?>"
                           data-device="<?php echo $device; ?>">
                    <input name="do[door43gitmerge-apply]" type="submit" class="door43gitmerge-apply"
                           value="<?php echo $this->getLang('apply'); ?>" data-page="<?php echo $ID; ?>"
                           data-frame="<?php echo $frame; ?>" data-device="<?php echo $device; ?>" data-action="apply">
                </span>
                <span class="door43gitmerge-edit-interface">
                    <input type="button" class="door43gitmerge-cancel" value="<?php echo $this->getLang('cancel'); ?>"
                           data-frame="<?php echo $frame; ?>" data-device="<?php echo $device; ?>">
                    <input type="reset" value="<?php echo $this->getLang('reset'); ?>">
                    <input name="do[door43gitmerge-apply-edited]" type="submit" class="door43gitmerge-apply-edited"
                           value="<?php echo $this->getLang('apply'); ?>" data-page="<?php echo $ID; ?>"
                           data-frame="<?php echo $frame; ?>" data-device="<?php echo $device; ?>"
                           data-action="apply-edited">
                </span>
                </div>
            </form>
        </div>
        <?php
    }
}

// vim:ts=4:sw=4:et:
