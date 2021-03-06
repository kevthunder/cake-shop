<?php
class ShopPromotionsController extends ShopAppController {

	var $name = 'ShopPromotions';
	var $uses = array('Shop.ShopPromotion','Shop.ShopProduct');
	var $components = array('Acl');

	function beforeFilter() {
		parent::beforeFilter();
		
		$this->set('operators', $this->ShopPromotion->operators);
	}
	
	/*function index() {
		$this->ShopPromotion->recursive = 0;
		$this->set('shopPromotions', $this->paginate());
	}

	function view($id = null) {
		if (!$id) {
			$this->Session->setFlash(sprintf(__('Invalid %s', true), 'shop promotion'));
			$this->redirect(array('action' => 'index'));
		}
		$this->set('shopPromotion', $this->ShopPromotion->read(null, $id));
	}*/

		
	function admin_test() {
		$this->ShopPromotion->create();
		
		//$this->ShopPromotion->Aco->save(array('alias'=>'promotions'));
		
		$this->render(false);
	}
	
	function admin_index() {
		$q = null;
		if(isset($this->params['named']['q']) && strlen(trim($this->params['named']['q'])) > 0) {
			$q = $this->params['named']['q'];
		} elseif(isset($this->data['Artist']['q']) && strlen(trim($this->data['Artist']['q'])) > 0) {
			$q = $this->data['Artist']['q'];
			$this->params['named']['q'] = $q;
		}
					
		if($q !== null) {
			$this->paginate['conditions']['OR'] = array('ShopPromotion.code LIKE' => '%'.$q.'%',
														'ShopPromotion.title_fre LIKE' => '%'.$q.'%',
														'ShopPromotion.title_eng LIKE' => '%'.$q.'%',
														'ShopPromotion.desc_fre LIKE' => '%'.$q.'%',
														'ShopPromotion.desc_eng LIKE' => '%'.$q.'%');
		}
		$this->paginate['order'] = 'ShopPromotion.created DESC';

		$this->ShopPromotion->recursive = 0;
		$shopPromotions = $this->paginate();
		
		//////////// calcul use count ////////////
		$ids = Set::extract('{n}.ShopPromotion.id',$shopPromotions);
		$useCounts = $this->ShopPromotion->ShopCoupon->find('all',array(
			'fields'=>array(
				'COUNT(*) as `uses`',
				'ShopCoupon.shop_promotion_id',
			),
			'conditions'=>array(
				'ShopCoupon.shop_promotion_id'=>$ids,
				'ShopOrder.status'=>array('ordered','paid','shipped'),
			),
			'group'=>'shop_promotion_id',
			'contain'=>array('ShopOrder')
		));
		$useCounts = array_combine(Set::extract('{n}.ShopCoupon.shop_promotion_id',$useCounts),Set::extract('{n}.0.uses',$useCounts));
		foreach($shopPromotions as &$promo){
			$promo['ShopPromotion']['uses'] = 
				!empty($useCounts[$promo['ShopPromotion']['id']])
				? $useCounts[$promo['ShopPromotion']['id']]
				: 0;
		}
		
		
		$this->set('shopPromotions', $shopPromotions);
		
		$this->set('operators', $this->ShopPromotion->operators);
	}

	function _promoMethodList(){
		$conds = array();
		$methods = array();
		if(ShopConfig::load('promo.complexBehavior') || ShopConfig::load('promo.complexConditions')) {
			App::import('Lib', 'Shop.ClassCollection'); 
			$all = ClassCollection::getList('promo','flat');
			foreach($all as $name => $label){
				$class = ClassCollection::getClass('promo',$name);
				if(!method_exists($class,'isAvailable') || call_user_func(array($class, 'isAvailable')) ){
					$obj = new $class();
					if(method_exists($class,'beforeForm')){
						$obj->beforeForm($this);
					}
					if(!empty($obj->type) && $obj->type = 'condition'){
						$conds[$name] = $obj;
					}else{
						$methods[$name] = $obj;
					}
				}
			}
		}
		if( !ShopConfig::load('promo.complexConditions') ) {
			$conds = array();
		}
		if( !ShopConfig::load('promo.complexBehavior') ) {
			App::import('Lib', 'Shop.ClassCollection'); 
			$class = ClassCollection::getClass('promo','Shop.operation');
			$obj = new $class();
			$methods = array('Shop.operation'=>$obj);
		}
		return array('methods'=>$methods,'conds'=>$conds);
	}

	function admin_add() {
		if (!empty($this->data)) {
			$this->ShopPromotion->create();
			if ($this->ShopPromotion->save($this->data)) {
				if(!empty($this->data['ShopPromotion']['aroProduct'])){
					$aro = $this->ShopProduct->Aro->read(null,$this->data['ShopPromotion']['aroProduct']);
					if(!empty($aro[$this->ShopProduct->Aro->alias]['alias'])){
						$aro = $aro[$this->ShopProduct->Aro->alias]['alias'];
					}else{
						$aro = $aro[$this->ShopProduct->Aro->alias];
					}
					$this->Acl->allow($aro, $this->ShopPromotion);
				}
				$this->Session->setFlash(sprintf(__('The %s has been saved', true), 'shop promotion'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(sprintf(__('The %s could not be saved. Please, try again.', true), 'shop promotion'));
			}
		}
		$products = $this->ShopProduct->generateAroList();
		$this->set('products', $products);
		$this->set($this->_promoMethodList());
		$actions = $this->ShopPromotion->ShopAction->find('list',array('conditions'=>array('status'=>'checkPromo')));
		$this->set('actions', $actions);
	}

	function admin_edit($id = null) {
		if (!$id && empty($this->data)) {
			$this->Session->setFlash(sprintf(__('Invalid %s', true), 'shop promotion'));
			$this->redirect(array('action' => 'index'));
		}
		$this->ShopPromotion->recursive = -1;
		$promotion = $this->ShopPromotion->read(null, $id);
		$aros = $this->ShopPromotion->productAros();
		if(!empty($aros)){
			$promotion[$this->ShopPromotion->alias]['aroProduct'] = set::extract('{n}.Aro.id',$aros);
		}
		if (!empty($this->data)) {
			if ($this->ShopPromotion->save($this->data)) {
				if(!empty($this->data['ShopPromotion']['aroProduct'])){
					//debug($this->ShopProduct->Aro->Permission->alias);
					//// unset old $aros ////
					foreach($aros as $aro){
						if(!empty($aro[$this->ShopProduct->Aro->alias]['alias'])){
							$aro = $aro[$this->ShopProduct->Aro->alias]['alias'];
						}else{
							$aro = $aro[$this->ShopProduct->Aro->alias];
						}
						$this->Acl->inherit($aro, $this->ShopPromotion);
					}
					//// set new $aros ////
					$aros = $this->ShopProduct->Aro->find('all',array('conditions'=>array('id'=>$this->data['ShopPromotion']['aroProduct'])));
					foreach($aros as $aro){
						if(!empty($aro[$this->ShopProduct->Aro->alias]['alias'])){
							$aro = $aro[$this->ShopProduct->Aro->alias]['alias'];
						}else{
							$aro = $aro[$this->ShopProduct->Aro->alias];
						}
						$this->Acl->allow($aro, $this->ShopPromotion);
					}
				}
				$this->Session->setFlash(sprintf(__('The %s has been saved', true), 'shop promotion'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(sprintf(__('The %s could not be saved. Please, try again.', true), 'shop promotion'));
			}
		}
		if (empty($this->data)) {
			$this->data = $promotion;
			//debug($this->data);
		}
		$this->ShopPromotion->ShopCoupon->recursive = -1;
		$coupons['all'] = $this->ShopPromotion->ShopCoupon->find('count',array('conditions'=>array('shop_promotion_id'=>$promotion['ShopPromotion']['id'])));
		$coupons['used'] = $coupons['all']-$this->ShopPromotion->ShopCoupon->find('count',array('conditions'=>array('shop_promotion_id'=>$promotion['ShopPromotion']['id'],'or'=>array('ShopCoupon.status not'=>'used','ShopCoupon.status'=> null))));
		$coupons['reserved'] = $coupons['all']-$this->ShopPromotion->ShopCoupon->find('count',array('conditions'=>array('shop_promotion_id'=>$promotion['ShopPromotion']['id'],'or'=>array('ShopCoupon.status not'=>array('used','reserved'),'ShopCoupon.status'=> null))));
		$this->set('coupons', $coupons);
		
		$products = $this->ShopProduct->generateAroList();
		$this->set('products', $products);
		$this->set($this->_promoMethodList());
		$actions = $this->ShopPromotion->ShopAction->find('list',array('conditions'=>array('status'=>'checkPromo')));
		$this->set('actions', $actions);
		$this->set('promotion', $promotion);
	}
	
	function admin_method_form($name,$prefix){
		$this->layout = 'ajax';
		App::import('Lib', 'Shop.ClassCollection'); 
		$class = ClassCollection::getClass('promo',$name);
		$method = new $class();
		if(method_exists ($method,'beforeForm')){
			$method->beforeForm($this);
		}
		$this->set('method',$method);
		$this->set('prefix',$prefix);
	}

	function admin_delete($id = null) {
		if (!$id) {
			$this->Session->setFlash(sprintf(__('Invalid id for %s', true), 'shop promotion'));
			$this->redirect(array('action'=>'index'));
		}
		if ($this->ShopPromotion->delete($id)) {
			$this->Session->setFlash(sprintf(__('%s deleted', true), 'Shop promotion'));
			$this->redirect(array('action'=>'index'));
		}
		$this->Session->setFlash(sprintf(__('%s was not deleted', true), 'Shop promotion'));
		$this->redirect(array('action' => 'index'));
	}
	
}
?>