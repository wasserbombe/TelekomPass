<?php
	namespace RZFuhrmann;

	if (!class_exists('RZFuhrmann\TelekomPass'))  {
		class TelekomPass {
			private $endpoint = 'https://pass.telekom.de/';

			/**
			 * Makes a simple GET request to a given URL. 
			 */
			private function getHTML($url){
				$ch = curl_init(); 
				curl_setopt($ch, CURLOPT_URL, $url); 
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE); 
				curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.140 Safari/537.36');
				$html = curl_exec($ch); 

				if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200){
					return false;
				}
				
				$doc = new \DOMDocument();
				@$doc->loadHTML($html);
				return $doc;
			}

			/**
			 * Get data usage by month (including current month)
			 * 
			 * Germany & EU: 	https://pass.telekom.de/tariffTransparency/domestic
			 * Abroad:			https://pass.telekom.de/tariffTransparency/roaming
			 */
			public function getDataUsage(){
				$doc = $this->getHTML($this->endpoint . 'tariffTransparency/domestic');
				// TODO: Parsing!
				return $doc->textContent; 
			}

			//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			// HELPER FUNCTIONS //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			/**
			 * Takes a DOMElement and checks whether the element has a specific class
			 */
			private function hasClass($DOMElement, $classname){
				if (!$DOMElement->getAttribute("class")) return false;
				if (preg_match("~(^| )".$classname."( |$)~", $DOMElement->getAttribute("class"))) return true;
				return false;
			}

		}

	}
?>