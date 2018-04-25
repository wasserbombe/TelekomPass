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

			/**
			 * Get current pass status from home page
			 */
			public function getStatus(){
				$res = array(
					"activePass" => null,
					"billingPeriod" => null,
					"remainingTime_text" => null,
					"remainingTime" => null,
					"tariffZone" => null,
					"data" => array(
						"currentDataUsage" => null,
						"currentDataPercentage" => null,
						"dataVolume" => null,
					)
				);
				$doc = $this->getHTML($this->endpoint . 'home');

				foreach ($doc->getElementsByTagName("h2") as $h2){
					if ($h2->getAttribute("id") == "pageTitle"){
						$res["activePass"] = $h2->textContent;
					}
				}

				// parse info lines
				$trs = $doc->getElementsByTagName("tr");
				foreach ($trs as $tr){
					if (!$this->hasClass($tr, "infoLine")) continue;

					foreach ($tr->getElementsByTagName("td") as $td){
						if ($this->hasClass($td, "infoValue")){
							if ($this->hasClass($td, "billingPeriod")){
								$res["billingPeriod"] = $td->textContent;
							} elseif ($this->hasClass($td, "remainingTime")){
								$res["remainingTime_text"] = $td->textContent;

								$res["remainingTime"] = 0; 

								// parse days
								if (preg_match("~([0-9]+) Tage?~", $res["remainingTime_text"], $matches)){
									$res["remainingTime"] += (int)$matches[1]*60*60*24;
								}

								// parse hours
								if (preg_match("~([0-9]+) Std~", $res["remainingTime_text"], $matches)){
									$res["remainingTime"] += (int)$matches[1]*60*60;
								}

								// parse minutes
								if (preg_match("~([0-9]+) Min\.~", $res["remainingTime_text"], $matches)){
									$res["remainingTime"] += (int)$matches[1]*60;
								}

								// TODO: Are there more parts?! 

							} elseif ($this->hasClass($td, "tariffZone")){
								$res["tariffZone"] = $td->textContent;
							} else {
								// unknown info
							}
						} 
					}
				}

				// parse data volume
				foreach ($doc->getElementsByTagName("div") as $div){
					if (!$this->hasClass($div, "barTextBelow")) continue; 

					if (preg_match("~([0-9]+,?[0-9]*).{2}(KB|GB) von ([0-9]+,?[0-9]*).{2}(KB|GB)~i", $div->textContent, $matches)){
						$res["data"]["currentDataUsage"] = (double)str_replace(",",".", $matches[1]);
						
						$res["data"]["dataVolume"] = (double)str_replace(",",".", $matches[3]);
						$res["data"]["currentDataPercentage"] = $res["data"]["currentDataUsage"]/$res["data"]["dataVolume"];

						break;
					}
				}

				return $res;

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