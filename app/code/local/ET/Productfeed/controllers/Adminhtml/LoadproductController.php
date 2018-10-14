<?php
	class ET_Productfeed_Adminhtml_LoadproductController extends Mage_Adminhtml_Controller_Action {
		/*
		 * Product Feed Loader
		 * Load product from Feed & Upload or Update File
		 * */
		public function loadFeedAction() {

			$totalUploaded = 0;  //-- total uploaded product
			$totalUpdated = 0;  //-- total updated product
			$errorMessage = false;   //-- error message declaration
			//-- first check if the module is enabled or not
			$_moduleEnabled = Mage::getStoreConfig('et_productfeed_settings/general/enable',Mage::app()->getStore());
			if($_moduleEnabled){
				//-- now getting product feed URL from settings & check for Valid URL
				$_feedURL = Mage::getStoreConfig('et_productfeed_settings/general/product_feed_url',Mage::app()->getStore());
				if (filter_var($_feedURL, FILTER_VALIDATE_URL)) {
					//-- read the json file
					$string = file_get_contents($_feedURL);
					$productArray = json_decode($string, true);
					if($productArray) {
						//-- looping over data to upload or update
						foreach ($productArray as $datum) {
							/*
							 * First check if product found or not
							 * if product found with SKU then we will update the product
							 * if product not found then we will create the product
							 * */
							$product = Mage::getModel('catalog/product');   //-- loading the product model
							$getProductSKU = $datum['sku'];
							if(!$product->getIdBySku($getProductSKU)){
								//-- now upload the product with protected uploadProduct() function
								$response = $this->uploadProduct($product, $datum);
								$totalUploaded = ($response)?$totalUploaded+1:$totalUploaded;
							}else {
								//-- now update the product with protected uploadProduct() function
								$response = $this->updateProduct($product, $datum);
								$totalUpdated = ($response)?$totalUpdated+1:$totalUpdated;
							}
						}
					}
				}else {
					//-- Feed URL is not in right format
					$errorMessage = "Feed URL is not in correct format";
				}
			}else {
				$errorMessage = "Sorry Product Feed is Disabled from Setting";
			}

			//-- response array
			$responseArray = array(
				"totalUploaded" => $totalUploaded,
				"totalUpdated" => $totalUpdated,
				"errorMessage" => $errorMessage
			);
			echo json_encode($responseArray);
		}

		/*
		 * Upload Product
		 * @param object $product
		 * @param array $productData
		 * @return boolean
		 * */
		protected function uploadProduct($product, $productData){
			//-- upload the new product
			$uploadStatus = false;
			try{
				$product
					->setStoreId(1) //--  set data in store scope
					->setWebsiteIds(array(1)) //-- website ID the product is assigned to, as an array
					->setAttributeSetId(4) //-- ID of a attribute set named 'default'
					->setTypeId('simple') //-- product type it can be six kind of type
					->setCreatedAt(strtotime('now')) //-- product creation time
					->setSku($productData['sku']) //-- SKU
					->setName($productData['name']) //-- product name
					->setWeight(($productData['weight']))   //-- product weight
					->setStatus(($productData['status'])) //-- product status (1 - enabled, 2 - disabled)
					->setTaxClassId(4) //-- tax class (0 - none, 1 - default, 2 - taxable, 4 - shipping)
					->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH) //-- catalog and search visibility
					//->setNewsFromDate('06/26/2019') //-- product set as new from
					//->setNewsToDate('06/30/2019') //-- product set as new to
					->setPrice($productData['price'])
					->setCost($productData['cost_price']);
					//-- Special Price Setting logic goes here
					if($productData['special_price'] && $productData['special_price'] > 0) {
						$product->setSpecialPrice($productData['special_price']);
					}
					$product
					//->setSpecialFromDate('06/1/2019') //-- special price from (MM-DD-YYYY)
					//->setSpecialToDate('06/30/2019') //-- special price to (MM-DD-YYYY)
					//->setMsrpEnabled(1) //-- enable MAP
					//->setMsrpDisplayActualPriceType(1) //-- display actual price (1 - on gesture, 2 - in cart, 3 - before order confirmation, 4 - use config)
					//->setMsrp(99.99) //-- Manufacturer's Suggested Retail Price
					->setMetaTitle($productData['name'])
					->setMetaDescription($productData['name'])
					->setDescription($productData['description'])
					->setShortDescription($productData['short_description'])
					//-- stock data goes here
					->setStockData(array(
							'use_config_manage_stock' => 0, //-- 'Use config settings' checkbox
							'manage_stock'=>$productData['manage_stock'], //-- manage stock
							'min_sale_qty'=>$productData['min_sale_qty'], //-- Minimum Qty Allowed in Shopping Cart
							'max_sale_qty'=>$productData['max_sale_qty'], //-- Maximum Qty Allowed in Shopping Cart
							'is_in_stock' => $productData['is_in_stock'], //-- Stock Availability
							'qty' => $productData['qty'] //-- qty
						)
					)
					->setCategoryIds(array(4)); //-- assign product to categories

				/*
				 * Add image with local code
				 * this can be used for internal used
				 * */
				$image_url  = $productData['image'];
				$image_url  = str_replace("https://", "http://", $image_url); //--  replace https tp http
				$image_type = substr(strrchr($image_url,"."),1); //-- find the image extension
				$filename   = $t=time().'.'.$image_type; //-- give a new name, you can modify as per your requirement
				$filepath   = Mage::getBaseDir('media') . DS . 'import'. DS . $filename; //--path for temp storage folder: ./media/import/
				//-- curl to save the file
				$curl_handle = curl_init();
				curl_setopt($curl_handle, CURLOPT_URL,$image_url);
				curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
				curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl_handle, CURLOPT_USERAGENT, 'Cirkel');
				$query = curl_exec($curl_handle);
				curl_close($curl_handle);

				//-- now save the file to our system
				file_put_contents($filepath, $query);  //-- store the image from external url to the temp storage folder file_get_contents(trim($image_url))
				$filepath_to_image = $filepath;
				if (file_exists($filepath_to_image)) {
					$product->addImageToMediaGallery($filepath_to_image, array('image', 'small_image', 'thumbnail'), false, false);
				}
				//-- finally save the product
				$product->save();
				$uploadStatus = true;
			}catch(Exception $e){
				$uploadStatus = false;
				//Mage::log($e->getMessage());
			}
			//-- return Data
			return $uploadStatus;
		}

		/*
		 * Update Product
		 * @param object $product
		 * @param array $productData
		 * @return boolean
		 * */
		protected function updateProduct($product, $productData){
			$updateStatus = false;
			try{
				//-- update the new product
				$SKU = $productData['sku'];
				$product = $product->loadByAttribute('sku',$SKU);
				if ($product) {
					//-- update product basic information
					$product
						->setName($productData['name'])
						->setStatus($productData['status'])
						->setPrice($productData['price'])
						->setCost($productData['cost_price']);
						//-- Special Price Setting logic goes here
						if($productData['special_price'] && $productData['special_price'] > 0) {
							$product->setSpecialPrice($productData['special_price']);
						}
					$product
						->setMetaTitle($productData['name'])
						->setMetaDescription($productData['name'])
						->setDescription($productData['description'])
						->setShortDescription($productData['short_description']);
					$product->save();

					//-- update product stock & inventory
					$productId = $product->getId();
					$stockItem =Mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);
					$stockItem->setData('manage_stock', $productData['manage_stock']);
					$stockItem->setData('is_in_stock', $productData['is_in_stock']);
					$stockItem->setData('qty', $productData['qty']);
					$stockItem->save();
				}
				$updateStatus = true;
			}catch(Exception $e){
				$updateStatus = false;
				//Mage::log($e->getMessage());
			}
			return $updateStatus;
		}
	}