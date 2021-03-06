<?php 
class CartMakerComponent extends Object{
	var $components = array('Session','Shop.ShopFunct','Shop.OrderMaker');
	
	var $controller = null;
	var $data = null;
	var $_itemListData = null;
	
	function initialize(&$controller) {
		$this->controller =& $controller;
		$this->initData();
		
	}
	function init(&$controller) {
		$this->controller =& $controller;
		$this->initData();
	}
	
	function initData(){
		$this->data = $this->Session->read('Shop.cart');
		if(empty($this->data)){
			$this->data = array('products'=>array());
		}
	}
	
	function beforeRender(){
		App::import('Lib', 'Shop.ShopConfig');
		if(ShopConfig::load('cart.qtyInNbItem')){
			$this->controller->params['Shop']['nbItem'] = $this->getTotalQty();
		}else{
			$this->controller->params['Shop']['nbItem'] = $this->nbItem();
		}
		$this->controller->params['Shop']['qtys'] = $this->qtysByProduct();
	}
	
	function itemList(){
		return $this->data['products'];
	}
	
	function nbItem(){
		return count($this->data['products']);
	}
	
	function getTotalQty(){
		$items = $this->data['products'];
		//pr($items);
		$qty = 0;
		if(!empty($items)){
			foreach($items as $k=>$item){
				$qty = $qty + $item['nb'];
			}
		}
		
		return $qty;
	}
	
	function qtysByProduct(){
		// debug($this->data['products']);
		$qts = array();
		foreach($this->data['products'] as $prod){
			$model = !empty($prod['model']) ? Inflector::classify($prod['model']) : 'shop_product';
			$id = !empty($prod['foreign_id']) ? $prod['foreign_id'] : $prod['id'];
			if(empty($qts[$model][$id])) $qts[$model][$id] = 0;
			$qts[$model][$id] += $prod['nb'];
		}
		//debug($qts);
		return $qts;
	}
	
	function getSubTotal(){
		$items = $this->itemListData();
		//pr($items);
		$subTotal = 0;
		if(!empty($items)){
			foreach($items as $k=>$item){
				$subTotal = $subTotal + ($item['DynamicField']['price'] * $item['Options']['nb']);
			}
		}
		
		return $subTotal;
	}
	
	function itemListData($minCalcul=true){
		if(!empty($this->data['cache']['itemListData'])){
			return $this->data['cache']['itemListData'];
		}
		
		$ShopProduct =& ClassRegistry::init('Shop.ShopProduct');
		$data = array();
		foreach($this->data['products'] as $product){
			$productData = array();
			$conditions = $this->ShopFunct->productFindConditions($product,array('tcheckActive'=>false));
			$productFind = $ShopProduct->find('first',array('conditions'=>$conditions));
			$productData['Options'] = $product;
			if(!empty($productFind)){
				$productData = array_merge($productData,$productFind);
			}elseif(!empty($product['model']) && !empty($product['foreign_id'])){
				$productData = $ShopProduct->getFullData($productData);
			}
			if($minCalcul){
				$productData = $this->ShopFunct->calculSubItem($productData);
				$productData = $this->ShopFunct->calculPromo($productData,isset($this->data['order'])?$this->data['order']:null);
			}
			if(!empty($productData)){
				$data[] = $productData;
			}
		}
		
		$this->data['cache']['itemListData'] = $data;
		$this->save();
		
		return $data;
	}
	
	function calculate(){
		if(!empty($this->data['cache']['calculate'])){
			return $this->data['cache']['calculate'];
		}
		
		$items = $this->itemListData(false);
		$order = array();
		if(isset($this->data['order'])){
			$order = $this->data['order'];
		}
		$res = $this->ShopFunct->calculate(array('order'=>$order,'items'=>$items));
		$res = $this->ShopFunct->reverseOrderItem(array('order'=>$order,'items'=>$items),$res);
		//debug($res);
		
		$this->data['cache']['calculate'] = $res;
		$this->save();
		
		return $res;
	}
	
	function taxeReady($order){
		$order = array();
		if(isset($this->data['order'])){
			$order = $this->data['order'];
		}
		$res = $this->ShopFunct->taxeReady(array('order'=>$order));
		return $res;
	}
	
	
	function gessLocation($address){
		$maps_host = "maps.google.com";
		$key = google();
		
		$delay = 0;
		$base_url = "http://" . $maps_host . "/maps/geo?output=xml" . "&key=" . $key;
		$geocode_pending = true;

		while ($geocode_pending) {
			$request_url = $base_url . "&q=" . urlencode($address);
			$xml = simplexml_load_file($request_url) or die("url not loading");

			$status = $xml->Response->Status->code;
			if (strcmp($status, "200") == 0) {
				$geocode_pending = false;
				$res = array(
					'address' => (string)$xml->Response->Placemark->address,
					'country' => (string)$xml->Response->Placemark->AddressDetails->Country->CountryNameCode,
					'region' => (string)$xml->Response->Placemark->AddressDetails->Country->AdministrativeArea->AdministrativeAreaName,
					'city' => (string)$xml->Response->Placemark->AddressDetails->Country->AdministrativeArea->Locality->LocalityName,
				);
				//debug($res);
				return $res;
			} else if (strcmp($status, "620") == 0) {
				// sent geocodes too fast
				$delay += 100000;
			} else {
				// failure to geocode
				$geocode_pending = false;
				$this->log("Address " . $address . " failed to geocoded.");
				$this->log("Received status " . $status );
			}
			usleep($delay);
		}
		return null;
	}
	
	function add($options){//s�parer option et produits ?
		$defaultOptions = array(
			'products' => array(),
			'redirect' => true,
			'back' => null,
		);
		if(!is_array($options)){
			$options = array('products'=>array($options));
		}
		if(!empty($options['products']) && is_array($options['products']) && empty($options['products'][0])){
			$options['products'] = array($options['products']);
		}
		$options = array_merge($defaultOptions,$options);
		foreach($options['products'] as $product){
			$product = $this->ShopFunct->formatProductAddOption($product);
			//debug($product);
			$pos = $this->match($product);
			if($pos>-1){
				$this->data['products'][$pos]['nb']+=$product['nb'];
				if($this->data['products'][$pos]['nb'] <= 0){
					$this->remove($pos);
				}
			}elseif($product['nb'] > 0){
				$this->data['products'][] = $product;
			}
		}
		//debug($this->data);
		//exit();
		$this->clearCache();
		$this->save();
		if($options['redirect']){
			$url = array('plugin'=>'shop', 'controller'=>'shop_cart', 'action' => 'index');
			if(!empty($options['back'])){
				App::import('Lib', 'Shop.UrlParam');
				//debug($options['back']);
				$url['redirect'] = UrlParam::encode($options['back']);
				//debug($options['back']);
				//debug(UrlParam::decode($options['back']));
			}
			$this->controller->redirect($url);
			exit();
		}
	}
	
	function changeQty($id, $qty = "+1"){
		if(substr($qty,0,3)=='add'){
			$this->data['products'][$id]['nb'] += substr($qty,3);
		}elseif(substr($qty,0,1)=='+'){
			$this->data['products'][$id]['nb'] += substr($qty,1);
		}elseif(substr($qty,0,1)=='-'){
			$this->data['products'][$id]['nb'] -= substr($qty,1);
		}else{
			$this->data['products'][$id]['nb'] = $qty;
		}
		
		$this->clearCache();
		
		if($this->data['products'][$id]['nb'] <= 0){
			$this->remove($id);
		} else{
			$this->save();
		}
	}
	
	function remove($condition = null){
		$deletedCount = 0;
		if(!is_array($condition) && !is_null($condition) && $condition!==false){
			$condition = array('num'=>$condition);
		}
		if(!empty($condition)){
			if(isset($condition['num'])){
				array_splice($this->data['products'], $condition['num'], 1);
			}else{
				$i = 0;
				while($i < count($this->data['products'])){
					$product = $this->data['products'][$i];
					if(!count(array_diff_assoc($condition,$product))){
						array_splice($this->data['products'], $i, 1);
						$deletedCount++;
					}else{
						$i++;
					}
				}
			}
		}
		$this->clearCache();
		$this->save();
		return $deletedCount;
	}
	
	function setOrderData($data){
		save(array('order'=>$data));
	}
	
	function toData(){
		$res = array('ShopCart' => $this->data);
		if(!empty($res['ShopCart']['ShopOrder'])){
			$res['ShopOrder'] = $res['ShopCart']['ShopOrder'];
			unset($res['ShopCart']['ShopOrder']);
		}
		return $res;
	}
	
	function save($data = null){
		if(!empty($data)){
			$nomalized = $data;
			if(isset($data['ShopCart'])){
				$nomalized = $data['ShopCart'];
			}
			if(isset($data['ShopOrder'])){
				$nomalized['order'] = $data['ShopOrder'];
				unset($nomalized['ShopOrder']);
			}
			if(!empty($nomalized['products'])){
				foreach($tmp = $nomalized['products'] as $no => $prod){
					if($prod['nb'] <= 0){
						//unset($nomalized['products'][$no]);
						array_splice($nomalized['products'],$no,1);
						$this->remove($no);
					}
				}
			}
			App::import('Lib', 'Shop.SetMulti');
			$this->data = SetMulti::complexMerge($this->data,$nomalized,array('sequences'=>false));
			
			$this->clearCache();
		}
		
		//debug($this->data);
		$this->Session->write('Shop.cart', $this->data);
		$this->_itemListData = null;
	}
	
	
	function clearCache(){
		unset($this->data['cache']);
	}
	
	
	function clear(){
		$this->data = array('products'=>array());
		$this->save();
	}
	
	function order_now(){
		$opt = array('products'=>$this->data['products'],'redirect'=>true);
		if(!empty($this->data['order'])){
			$opt['order'] = $this->data['order'];
		}
		$this->OrderMaker->add($opt);
	}
	
	function match($product){
		$conditions = $this->ShopFunct->productFindConditions($product,array('tcheckActive'=>false));
		if(!empty($product['SubItem'])){
			$conditions['SubItem'] = $product['SubItem'];
		}else{
			$conditions['SubItem'] = null;
		}
		return $this->indexOf($conditions);
	}
	
	////////////////// private //////////////////
	function indexOf($condition,$start=0){
		if(!is_array($condition)){
			$condition = array('id'=>$condition);
		}
		if(!empty($condition)){
			$i = $start;
			foreach($this->data['products'] as $index => $product){
				$product = array_intersect_key($product,$condition);
				if(!count(Set::diff( $condition,$product))){
					return $index;
				}
				$i++;
			}
		}
		return -1;
	}
	
} 
?>