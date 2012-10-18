<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * Codeigniter Minify Library
 *
 * @package		Codeigniter Library
 * @author		Dwi Setiyadi / @dwisetiyadi
 * @license		http://www.gnu.org/licenses/gpl.html
 * @link		http://dwi.web.id
 * @version		1.0
 * Last changed	4 Okt, 2012
 * Minify your css and javascript file on the fly.
 */

// ------------------------------------------------------------------------

class Minify {
	var $jspath = '';
	var $csspath = '';
	
	public function __construct($params = array()) {
		$CI =& get_instance();
		
		if (isset($params['jspath'])) {
			$this->jspath = FCPATH.trim($params['jspath'], '/');
		} else {
			$this->jspath = FCPATH.trim($CI->config->item('jspath'), '/');
		}
		if (isset($params['csspath'])) {
			$this->csspath = FCPATH.trim($params['csspath'], '/');
		} else {
			$this->csspath = FCPATH.trim($CI->config->item('csspath'), '/');
		}
	}
	
	public function build($type = 'js', $file = '') {
		if ($type === 'js') $path = $this->jspath;
		if ($type === 'css') $path = $this->csspath;
		if ( ! isset($path)) return;
		
		if (is_array($file)) {
			$buffer = array();
			$compressname = '';
			foreach ($file as $key=>$value) {
				$files = explode('/', $value);
				$count_files = count($files);
				if ($count_files > 1) {
					$value = end($files);
					$key = $count_files - 1;
					unset($files[$key]);
					
					$addpath = implode('/', $files);
					$path = $path.'/'.$addpath;
				}
				if (file_exists($path.'/'.$value)) {
					$buffer[$key] = $this->compressor($type, file_get_contents($path.'/'.$value));
					$compressname .= $value;
				}
			}
			if (count($buffer) > 0) {
				$buffer = implode("\n", $buffer);
				$compressname = md5($compressname).'.min';
			} else {
				$buffer = '';
				$compressname = 'none.min';
			}
		} else {
			$files = explode('/', $file);
			$count_files = count($files);
			if ($count_files > 1) {
				$file = end($files);
				$key = $count_files - 1;
				unset($files[$key]);
				
				$addpath = implode('/', $files);
				$path = $path.'/'.$addpath;
			}
		
			if (file_exists($path.'/'.$file)) {
				$buffer = $this->compressor($type, file_get_contents($path.'/'.$file));
				$compressname = md5($file).'.min';
			} else {
				$buffer = '';
				$compressname = 'none.min';
			}
		}
		
		
		if (file_exists($path.'/'.$compressname.'.'.$type)) {
			chmod($path.'/'.$compressname.'.'.$type, 0777);
			unlink($path.'/'.$compressname.'.'.$type);
		}
		
		if ( ! $fp = @fopen($path.'/'.$compressname.'.'.$type, FOPEN_WRITE_CREATE_DESTRUCTIVE)) return;		
		flock($fp, LOCK_EX);
		fwrite($fp, $buffer);
		flock($fp, LOCK_UN);
		fclose($fp);
		
		$path = base_url().str_replace(FCPATH, '', $path.'/'.$compressname.'.'.$type.'?h='.md5_file($path.'/'.$compressname.'.'.$type));
		return $path;
	}
	
	private function compressor($type = 'js', $buffer) {
		if ($type === 'js') {
			/* remove comments */
			$buffer = preg_replace("/((?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:\/\/.*))/", "", $buffer);
			
			/* remove tabs, spaces, newlines, etc. */
			$buffer = str_replace(array("\r\n","\r","\t","\n",'  ','    ','     '), '', $buffer);
			
			/* remove other spaces before/after ) */
			$buffer = preg_replace(array('(( )+\))','(\)( )+)'), ')', $buffer);
			return $buffer;
		}
		
		if ($type === 'css') {
			/* remove comments */
			$buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
			
			/* remove tabs, spaces, newlines, etc. */
			$buffer = str_replace(array("\r\n","\r","\n","\t",'  ','    ','     '), '', $buffer);
			
			/* remove other spaces before/after ; */
			$buffer = preg_replace(array('(( )+{)','({( )+)'), '{', $buffer);
			$buffer = preg_replace(array('(( )+})','(}( )+)','(;( )*})'), '}', $buffer);
			$buffer = preg_replace(array('(;( )+)','(( )+;)'), ';', $buffer);
			return $buffer;
		}
		
		return;
	}
}