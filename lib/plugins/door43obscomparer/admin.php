<?php
/**
 * DokuWiki Plugin comparer (Admin Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Richard Mahn <rich@themahn.com>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class admin_plugin_door43obscomparer extends DokuWiki_Admin_Plugin {
	/**
	 * @var door43Cache
	 */
	private $cache;

	private $source;
	private $target;
	private $ignore_ws;
	private $ignore_punct;
	private $json;
	private $languages;
	private $catalog;

	private $data = array();

	/**
	 * @return int sort number in admin menu
	 */
	public function getMenuSort() {
		return 10;
	}

	/**
	 * @return bool true if only access for superuser, false is for superusers and moderators
	 */
	public function forAdminOnly() {
		return false;
	}

	/**
	 * Should carry out any processing required by the plugin.
	 */
	public function handle() {
		$this->source = (isset($_GET['s'])?$_GET['s']:'en');
		$this->target = (isset($_GET['t'])?$_GET['t']:'');
		$this->ignore_ws = (isset($_GET['ignore_ws'])?true:false);
		$this->ignore_punct = (isset($_GET['ignore_punct'])?true:false);
		$this->json = (isset($_GET['json'])?true:false);

// Read the catalog json file to get all the langauges that have been released for OBS
		$catalogJson = json_decode(file_get_contents('https://api.unfoldingword.org/obs/txt/1/obs-catalog.json'), true);
		$this->catalog = array();
		$this->languages = array();
		foreach($catalogJson as $info){
			$this->catalog[$info['language']] = $info;
			$this->languages[] = $info['language'];
		}

// Make sure the selected source language is in the array of languages
		if(! in_array($this->source, $this->languages)){
			$this->source = 'en';
			$this->target = '';
		}

// Make sure that selected target language is in the array of languages or is 'ALL'
		if($this->target && ! in_array($this->target, $this->languages) && $this->target != 'ALL'){
			$this->target = '';
		}
	}

	/**
	 * Render HTML output, e.g. helpful text and a form
	 */
	public function html() {
// We only want to get the data and stats on the data if source and target have been passed
		if($this->source && $this->target){
			//First work with the source data and get its data into the data array
			$this->populate_data($this->source);
			$this->get_stats($this->source);

			if($this->target == 'ALL'){
				foreach($this->languages as $language) {
					if($language != $this->source) {
						$this->populate_data($language);
						$this->get_stats($language);
						$this->collate_with_source($language);
					}
				}
			}
			else {
				$this->populate_data($this->target);
				$this->get_stats($this->target);
				$this->collate_with_source($this->target);
			}

			if($this->json){
				echo json_encode($this->data);
				exit;
			}
		}

		$html = "";

		$html .= '<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">';

		$html .= '
<form method="get">
<input type="hidden" name="do" value="'.$_GET['do'].'">
<input type="hidden" name="page" value="'.$_GET['page'].'">
	Source:
	<select id="source-language" name="s">';
		foreach($this->languages as $language){
			$html .= '<option value="'.$language.'"'.($language==$this->source?' selected="selected"':'').'>'.$this->catalog[$language]['string'].' ('.$language.')'.'</option>';
		}
		$html .= '</select>

	Target:
	<select id="target-language" name="t">
		<option value="ALL">All</option>';
		foreach($this->languages as $language){
			$html .= '<option value="'.$language.'"'.($language==$this->target?' selected="selected"':'').'>'.$this->catalog[$language]['string'].' ('.$language.')'.'</option>';
		}
		$html .= '</select>

	<input type="submit" value="Submit"/>

	<br/>

	<input type="checkbox" name="ignore_ws" value="1"'.($this->ignore_ws?' checked="checked"':'').'> Ignore spaces
	<input type="checkbox" name="ignore_punct" value="1"'.($this->ignore_punct?' checked="checked"':'').'> Ignore punctuation
	<input type="checkbox" name="json" value="1"'.($this->json?' checked="checked"':'').'> Output as JSON

	<br/>
</form>';

		if(! empty($this->data) && $this->target && $this->source){
			$html .= '<div class="clear">';
			foreach($this->data as $language=>$info) {
				if ($language == $this->source)
					continue;

				$ratio = $info['stats']['frameMedianRatio'];
				$lowestRatio = $ratio - .2;
				$highestRatio = $ratio + .2;
				$html .= '<div class="language" id="' . $language . '">
				<div class="container">
					<div class="heading">Target: ' . $this->catalog[$language]['string'] . ' (' . $language . ')' . ' <a href="https://door43.org/' . $language . '/obs/" style="text-decoration:none;font-size:.8em;font-weight:normal;" target="_blank"><i class="fa fa-external-link"></i></a></span></div>
					<div class="item clear-left break">Overall Character Count: ' . number_format($info['stats']['count']) . ' (Target), ' . number_format($info['stats']['countSource']) . ' (Source)</div>
					<div class="item">Ratio: ' . sprintf("%.2f", $info['stats']['countRatio'] * 100) . '%</div>
					<div class="item clear-left"><span style="font-weight:bold;">Median Ratio: ' . sprintf("%.2f", $info['stats']['frameMedianRatio'] * 100) . '% <== This ratio will be used to find frames with a variance > 20%</span></div>
					<div class="item clear-left"><span style="font-weight:normal;">Average Ratio: ' . sprintf("%.2f", $info['stats']['frameAverageRatio'] * 100) . '%</span></div>
					<div class="item clear-left' . ($info['stats']['frameLowRatio'] < $lowestRatio ? ' warning' : '') . '">Lowest Ratio: ' . sprintf("%.2f", $info['stats']['frameLowRatio'] * 100) . '% (' . number_format($info['stats']['frameLow']) . ':' . number_format($info['stats']['frameLowSource']) . ')</div>
					<div class="item' . ($info['stats']['frameLowRatio'] < $lowestRatio ? ' warning' : '') . '">Variance: ' . sprintf("%+.2f", ($info['stats']['frameLowRatio'] - $ratio) * 100) . '%</div>
					<div class="item clear-left' . ($info['stats']['frameHighRatio'] > $highestRatio ? ' warning' : '') . '">Highest Ratio: ' . sprintf("%.2f", $info['stats']['frameHighRatio'] * 100) . '% (' . number_format($info['stats']['frameHigh']) . ':' . number_format($info['stats']['frameHighSource']) . ')</div>
					<div class="item' . ($info['stats']['frameHighRatio'] > $highestRatio ? ' warning' : '') . '">Variance: ' . sprintf("%+.2f", ($info['stats']['frameHighRatio'] - $ratio) * 100) . '%</div>
					<div class="item toggle-container"><a href="#" class="toggle">▼</a></div>
				</div>

				<div class="chapters" id="' . $language . '-chapters" style="display:none">';
				foreach($info['chapters'] as $chapterIndex=>$chapter){
					$html .= '<div class="chapter" id="' . $language . '-chapter-' . $chapter['number'] . '">
							<div class="container">
								<div class="heading">Chapter: ' . $chapter['title'] . ' <a href="https://door43.org/' . $language . '/obs/' . $chapter['number'] . '" style="text-decoration:none;font-size:.8em;font-weight:normal;" target="_blank"><i class="fa fa-external-link"></i></a></div>
								<div class="item clear-left break">Chapter Character Count: ' . number_format($chapter['stats']['count']) . ' (Target), ' . number_format($chapter['stats']['countSource']) . ' (Source)</div>
								<div class="item">Ratio: ' . sprintf("%.2f", $chapter['stats']['countRatio'] * 100) . '%</div>
								<div class="item clear-left' . ($chapter['stats']['frameLowRatio'] < $lowestRatio ? ' warning' : '') . '">Lowest Ratio: ' . sprintf("%.2f", $chapter['stats']['frameLowRatio'] * 100) . '% (' . number_format($chapter['stats']['frameLow']) . ':' . number_format($chapter['stats']['frameLowSource']) . ')</div>
								<div class="item' . ($chapter['stats']['frameLowRatio'] < $lowestRatio ? ' warning' : '') . '">Variance: ' . sprintf("%+.2f", ($chapter['stats']['frameLowRatio'] - $ratio) * 100) . '%</div>
								<div class="item clear-left' . ($chapter['stats']['frameHighRatio'] > $highestRatio ? ' warning' : '') . '">Highest Ratio: ' . sprintf("%.2f", $chapter['stats']['frameHighRatio'] * 100) . '% (' . number_format($chapter['stats']['frameHigh']) . ':' . number_format($chapter['stats']['frameHighSource']) . ')</div>
								<div class="item' . ($chapter['stats']['frameHighRatio'] > $highestRatio ? ' warning' : '') . '">Variance: ' . sprintf("%+.2f", ($chapter['stats']['frameHighRatio'] - $ratio) * 100) . '%</div>
								<div class="item toggle-container"><a href="#" class="toggle">▼</a></div>
							</div>

							<div class="frames" id="' . $language . '-' . $chapter['number'] . '-frames" style="display:none">';
					foreach($chapter['frames'] as $frameIndex=>$frame){
						$html .= '<div class="frame" id="' . $language . '-frame-' . $frame['id'] . '">
										<div class="container' . ($frame['stats']['countRatio'] < $lowestRatio || $frame['stats']['countRatio'] > $highestRatio ? ' warning' : '') . '">
											<div class="heading">Frame: ' . $frame['id'] . ' <a href="https://door43.org/' . $language . '/obs/' . $chapter['number'] . '" style="text-decoration:none;font-size:.8em;font-weight:normal;" target="_blank"><i class="fa fa-external-link"></i></a></div>
											<div class="item clear-left">Ratio: ' . sprintf("%.2f", $frame['stats']['countRatio'] * 100) . '% (' . number_format($frame['stats']['count']) . ':' . number_format($frame['stats']['countSource']) . ')</div>
											<div class="item">Variance: ' . sprintf("%+.2f", ($frame['stats']['countRatio'] - $ratio) * 100) . '%</div>
											<div class="item toggle-container"><a href="#" class="toggle">▼</a></div>
										</div>

										<div class="sentences clear-left" id="' . $language . '-frame-' . $frame['id'] . '-sentences" style="display:none;">
											<img src="' . $frame['img'] . '" />
											<p>
												' . $this->source . ':<br/>
											<pre>' . $this->data[$this->source]['chapters'][$chapterIndex]['frames'][$frameIndex]['transformedText'] . '</pre>
											</p>
											<p>
												' . $language . ':<br/>
											<pre>' . $frame['transformedText'] . '</pre>
											</p>';
						$html .= '</div>
									</div>';
					}
					$html .= '</div>
						</div>';
				}
				$html .= '</div>';
				$html .= '</div>';
			}
			$html .= '</div>';
		}
		else {
			$html .= '<div class="clear">
		<hr/>
		<div class="heading">Summary:</div>
		<p>
			This tool uses the json files located at <a href="https://api.unfoldingword.org/obs/txt/1/">https://api.unfoldingword.org/obs/txt/1/</a>
			to determine if the text of a frame of a given target language falls within ±20% of the normal percentage ratio between it and the source language.
		</p>
		<div class="heading">Description:</div>
		<p>
			The purpose of this tool is to determine if the text of a frame of OBS is outside the bounds of the normal ratio between the target language and
			the source language (usually English). This goes by the understanding that the text of most languages will normally longer or shorter than a sentence or paragraph in English.
		</p>
		<p>
			For example, in Chinese, something can be written in way fewer characters than English. "今天我要去超市买电脑。" is "Today I will go to the store to buy a computer.",
			the Chinese being 11 characters, the English being 47 characters, a ratio of 11:47, or a percentage ratio of 23.4%. (if you have it ignore punctuation and spaces,
			it is 10:36 or 27.77%). On the other hand, French sentences are usually longer than a sentence with the same meaning in English, having a little more than a 110%
			percent ratio.
		</p>
		<p>
			Once a source and target language is chosen, this tool will gather all the frames of both the source and target language, get the percentage ratio of each target frame
			with its corresponding source frame (target length / source length), and then from all those target/source ratios, select the percentage ratio that is the median.
			This is then used to determine the variance of a given frame is more or less than 20% of the target languages ratio with the source language.
		</p>
		<div class="heading">Notes/Concerns:</div>
		<ul>
			<li><b>Median, average, or...?</b> Is selecting the median from the pool of all frame target-source ratios the best way to get the normal ratio of a language?</li>
			<li><b>Longer vs. shorter text?</b> Is the ratio different when the source text is short (a short sentence) compared to when there is a lot of text (4-5 sentences?)</li>
			<li><b>Variance - what range?</b> Is ±20% the best variance to use to say if a text\'s translation should be re - evaluated ? Should this be different for text that is short, or text that is long ?</li >
			<li ><b > Spaces and Punctuation ?</b > Is it more reliable to ignore spaces and or punctuation for both source and target ? Or better to keep them in ? Or based on target language ?
				Chinese has no spaces, and German often has very long phrases or words without spaces where English has spaces(e . g . Freundschaftsbezeigungen = demonstrations of friendship).</li >
		</ul >
	</div >';
		}

		echo $this->locale_xhtml('intro');
		echo($html);
	}

	function populate_data($language)
	{
		if(! isset($this->data[$language])) {
			$url = "https://api.unfoldingword.org/obs/txt/1/$language/obs-$language.json";
			$content = file_get_contents($url);
			$this->data[$language] = json_decode($content, true);
		}
	}

	function get_stats($language){
		$langData = &$this->data[$language];

		$langData['stats']['count'] = 0;
		$langData['stats']['frameCount'] = array();

		foreach($langData['chapters'] as $chapterIndex=>&$chapter){
			$chapter['stats']['count'] = 0;
			$chapter['stats']['frameCount'] = array();

			foreach($chapter['frames'] as $frameIndex=>&$frame){
				$text = $frame['text'];

				if($this->ignore_punct){
					$text = preg_replace("/\p{P}/u", "", $text);
				}

				if($this->ignore_ws) {
					$text = preg_replace('/\s+/', '', $text);
				}

				$length = mb_strlen($text, 'UTF-8');

				$frame['transformedText'] = $text;
				$frame['stats']['count'] = $length;
				$chapter['stats']['frameCount'][]  = $length;
				$langData['stats']['frameCount'][] = $length;
				$chapter['stats']['count'] += $length;
				$langData['stats']['count'] += $length;
			}
		}

// Now get stats
		$arr = $langData['stats']['frameCount'];
		sort($arr);
		$langData['stats']['frameLow'] = $arr[0];
		$langData['stats']['frameMedian'] = $this->calculate_median($arr);
		$langData['stats']['frameAverage'] = $this->calculate_average($arr);
		$langData['stats']['frameHigh'] = end($arr);

		foreach($langData['chapters'] as $chapterIndex=>&$chapter){
			$arr = $chapter['stats']['frameCount'];
			sort($arr);
			$chapter['stats']['frameLow'] = $arr[0];
			$chapter['stats']['frameMedian'] = $this->calculate_median($arr);
			$chapter['stats']['frameAverage'] = $this->calculate_average($arr);
			$chapter['stats']['frameHigh'] = end($arr);
		}
	}

	function calculate_median($arr) {
		sort($arr);
		$count = count($arr); //total numbers in array
		$middleval = (int)floor(($count-1)/2); // find the middle value, or the lowest middle value
		if($count % 2) { // odd number, middle is the median
			$median = $arr[$middleval];
		} else { // even number, calculate avg of 2 medians
			$low = $arr[$middleval];
			$high = $arr[$middleval+1];
			$median = (($low+$high)/2);
		}
		return $median;
	}

	function calculate_average($arr) {
		$count = count($arr); //total numbers in array
		$total = 0;
		foreach ($arr as $value) {
			$total = $total + $value; // total value of array numbers
		}
		$average = ($total/$count); // get average value
		return $average;
	}

	function collate_with_source($language){
		$srcData = $this->data[$this->source];
		$tarData = &$this->data[$language];

		unset($frameLowRatio);
		unset($frameHighRatio);
		unset($chapterLowRatio);
		unset($chapterHighRatio);
		$sourceTargetRatios = array();
		foreach($tarData['stats']['frameCount'] as $index=>$count){
			if( isset($srcData['stats']['frameCount'][$index]) && $srcData['stats']['frameCount'][$index] > 0) {
				$sourceTargetRatio = $count / $srcData['stats']['frameCount'][$index];
			}
			else {
				$sourceTargetRatio = 0;
			}

			if(! isset($frameLowRatio) || $sourceTargetRatio < $frameLowRatio){
				$frameLow = $count;
				$frameLowSource = $srcData['stats']['frameCount'][$index];
				$frameLowRatio = $sourceTargetRatio;
			}

			if(! isset($frameHighRatio) || $sourceTargetRatio > $frameHighRatio){
				$frameHigh = $count;
				$frameHighSource = $srcData['stats']['frameCount'][$index];
				$frameHighRatio = $sourceTargetRatio;
			}

			$sourceTargetRatios[] = $sourceTargetRatio;
		}
		$frameMedianRatio = $this->calculate_median($sourceTargetRatios);
		$frameAverageRatio = $this->calculate_average($sourceTargetRatios);

		$tarData['stats']['countSource'] = $srcData['stats']['count'];
		$tarData['stats']['countRatio'] = $tarData['stats']['count'] / $srcData['stats']['count'];

		$tarData['stats']['frameLow'] = $frameLow;
		$tarData['stats']['frameLowSource'] = $frameLowSource;
		$tarData['stats']['frameLowRatio'] = $frameLowRatio;

		$tarData['stats']['frameMedianSource'] = $srcData['stats']['frameMedian'];
		$tarData['stats']['frameMedianRatio'] = $frameMedianRatio;

		$tarData['stats']['frameAverageSource'] = $srcData['stats']['frameAverage'];
		$tarData['stats']['frameAverageRatio'] = $frameAverageRatio;

		$tarData['stats']['frameHigh'] = $frameHigh;
		$tarData['stats']['frameHighSource'] = $frameHighSource;
		$tarData['stats']['frameHighRatio'] = $frameHighRatio;

		$median = $tarData['stats']['frameMedianRatio'];

		foreach($tarData['chapters'] as $chapterIndex=>&$chapter){
			unset($frameLowRatio);
			unset($frameHighRatio);
			$sourceTargetRatios = array();
			foreach($chapter['stats']['frameCount'] as $index=>$count){
				if( isset($srcData['chapters'][$chapterIndex]['stats']['frameCount'][$index]) && $srcData['chapters'][$chapterIndex]['stats']['frameCount'][$index] > 0) {
					$sourceTargetRatio = $count / $srcData['chapters'][$chapterIndex]['stats']['frameCount'][$index];
				}
				else {
					$sourceTargetRatio = 0;
				}

				if(! isset($frameLowRatio) || $sourceTargetRatio < $frameLowRatio){
					$frameLow = $count;
					$frameLowSource = $srcData['chapters'][$chapterIndex]['stats']['frameCount'][$index];
					$frameLowRatio = $sourceTargetRatio;
				}

				if(! isset($frameHighRatio) || $sourceTargetRatio > $frameHighRatio){
					$frameHigh = $count;
					$frameHighSource = $srcData['chapters'][$chapterIndex]['stats']['frameCount'][$index];
					$frameHighRatio = $sourceTargetRatio;
				}

				$sourceTargetRatios[] = $sourceTargetRatio;
			}
			$frameMedianRatio = $this->calculate_median($sourceTargetRatios);
			$frameAverageRatio = $this->calculate_average($sourceTargetRatios);

			$chapter['stats']['countSource'] = $srcData['chapters'][$chapterIndex]['stats']['count'];
			$chapter['stats']['countRatio'] = $chapter['stats']['count'] / $srcData['chapters'][$chapterIndex]['stats']['count'];

			$chapter['stats']['frameLow'] = $frameLow;
			$chapter['stats']['frameLowSource'] = $frameLowSource;
			$chapter['stats']['frameLowRatio'] = $frameLowRatio;

			$chapter['stats']['frameMedianSource'] = $srcData['chapters'][$chapterIndex]['stats']['frameMedian'];
			$chapter['stats']['frameMedianRatio'] = $frameMedianRatio;

			$chapter['stats']['frameAverageSource'] = $srcData['chapters'][$chapterIndex]['stats']['frameAverage'];
			$chapter['stats']['frameAverageRatio'] = $frameAverageRatio;

			$chapter['stats']['frameHigh'] = $frameHigh;
			$chapter['stats']['frameHighSource'] = $frameHighSource;
			$chapter['stats']['frameHighRatio'] = $frameHighRatio;

			foreach($chapter['frames'] as $frameIndex=>&$frame){
				$frame['stats']['countSource'] = $srcData['chapters'][$chapterIndex]['frames'][$frameIndex]['stats']['count'];
				$frame['stats']['countRatio'] = $frame['stats']['count'] / $srcData['chapters'][$chapterIndex]['frames'][$frameIndex]['stats']['count'];
			}
		}
	}
}
