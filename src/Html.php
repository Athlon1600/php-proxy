<?php

namespace Proxy;

class Html {
	
	public static function remove_scripts($html){
		$html = preg_replace('/<\s*script[^>]*>(.*?)<\s*\/\s*script\s*>/is', '', $html);
		return $html;
	}
	
	public static function remove_styles($html){
		$html = preg_replace('/<\s*style[^>]*>(.*?)<\s*\/\s*style\s*>/is', '', $html);
		return $html;
	}
	
	public static function remove_comments($html){
		return preg_replace('/<!--(.*?)-->/s', '', $html);
	}

	private static function find($selector, $html, $start_from = 0){
	
		$html = substr($html, $start_from);
		
		$inner_start = 0;
		$inner_end = 0;
		
		$pattern = '//';
		
		if(substr($selector, 0, 1) == '#'){
			$pattern = '/<(\w+)[^>]+id="'.substr($selector, 1).'"[^>]*>/is';
		} else if(substr($selector, 0, 1) == '.'){
			$pattern = '/<(\w+)[^>]+class="'.substr($selector, 1).'"[^>]*>/is';
		} else {
			return false;
		}

		if(preg_match($pattern, $html, $matches, PREG_OFFSET_CAPTURE)){
			
			$outer_start = $matches[0][1];
			$inner_start = $matches[0][1] + strlen($matches[0][0]);
			
			// tag stuff
			$tag_name = $matches[1][0];
			$tag_len = strlen($tag_name);
			
			$run_count = 300;
			
			// "open" <tag elements we found so far
			$open_count = 1;
			$start = $inner_start;
			
			while($open_count != 0 && $run_count-- > 0){

				$open_tag = strpos($html, "<{$tag_name}", $start);
				$close_tag = strpos($html, "</{$tag_name}", $start);
				
				// nothing was found?
				if($open_tag === false && $close_tag === false){
					break;
				}
				
				//echo "open_tag: {$open_tag}, close_tag {$close_tag}\r\n";
				
				// found OPEN tag
				if($close_tag === false || ($open_tag !== false && $open_tag < $close_tag) ){
					$open_count++;
					$start = $open_tag + $tag_len + 1;
					
					//echo "found open tag: ".substr($html, $open_tag, 20)." at {$open_tag} \r\n";
					
				// found CLOSE tag
				} else if($open_tag === false || ($close_tag !== false && $close_tag < $open_tag) ){
					$open_count--;
					$start = $close_tag + $tag_len + 2;
					
					//echo "found close tag: ".substr($html, $close_tag, 20)." at {$close_tag} \r\n";
				}
			}
			
			// something went wrong... don't bother returning anything
			if($open_count != 0){
				return false;
			}
			
			$outer_end = $close_tag + $tag_len + 3;
			$inner_end = $close_tag;
			
			return array(
				'outer_start' => $outer_start + $start_from,
				'inner_start' => $inner_start + $start_from,
				'inner_end' => $inner_end + $start_from,
				'outer_end' => $outer_end + $start_from
			);
		}
		
		return false;
	}
	
	public static function extract_inner($selector, $html){
		return self::extract($selector, $html, true);
	}
	
	public static function extract_outer($selector, $html){
		return self::extract($selector, $html, false);
	}
	
	private static function extract($selector, $html, $inner = false){
	
		$pos = 0;
		$limit = 300;
		
		$result = array();
		$data = false;
		
		do {
		
			$data = self::find($selector, $html, $pos);
			
			if($data){
			
				$code = substr($html, $inner ? $data['inner_start'] : $data['outer_start'], 
				$inner ? $data['inner_end'] - $data['inner_start'] : $data['outer_end'] - $data['outer_start']);
				
				$result[] = $code;
				$pos = $data['outer_end'];
			}
		
		} while ($data && --$limit > 0);
		
		return $result;
	}
	
	public static function remove($selector, $html){
		return self::replace($selector, '', $html, false);
	}
	
	public static function replace_outer($selector, $replace, $html, &$matches = NULL){
		return self::replace($selector, $replace, $html, false, $matches);
	}
	
	public static function replace_inner($selector, $replace, $html, &$matches = NULL){
		return self::replace($selector, $replace, $html, true, $matches);
	}
	
	private static function replace($selector, $replace, $html, $replace_inner = false, &$matches = NULL){
	
		$start_from = 0;
		$limit = 300;
		
		$data = false;
		$replace = (array)$replace;

		do {
		
			$data = self::find($selector, $html, $start_from);
			
			if($data){
			
				$r = array_shift($replace);
				
				// from where to where will we be replacing?
				$replace_space = $replace_inner ? $data['inner_end'] - $data['inner_start'] : $data['outer_end'] - $data['outer_start'];
				$replace_len = strlen($r);
				
				if($matches !== NULL){
					$matches[] = substr($html, $replace_inner ? $data['inner_start'] : $data['outer_start'], $replace_space);
				}
				
				$html = substr_replace($html, $r, $replace_inner ? $data['inner_start'] : $data['outer_start'], $replace_space);
				
				// next time we resume search at position right at the end of this element
				$start_from = $data['outer_end'] + ($replace_len - $replace_space);
			}
		
		} while ($data && --$limit > 0);
		
		return $html;
	}
}

?>