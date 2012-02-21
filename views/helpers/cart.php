<?php
class CartHelper extends AppHelper {

	var $helpers = array('Html', 'Form', 'Javascript', 'O2form.O2form');
	
	function buyUrl($id,$nb=null,$model=null,$options=array()){
		if(is_array($id)){
			$options = $id;
		}else{
			$options['id'] = $id;
			if(!empty($nb)){
				$options['nb'] = $nb;
			}
			if(!empty($model)){
				$options['model'] = $model;
			}
		}
		$defmodel = $this->model();
		if($defmodel == null){
			$defmodel = Inflector::classify($this->params['controller']);
		}
		$defaultOptions = array(
			'model' => $defmodel,
			'nb' => 1
		);
		$options = array_merge($defaultOptions,$options);
		return $this->Html->url(array('plugin'=>'shop','controller'=>'shop_cart','action'=>'add', 'model'=>$options['model'], 'id'=>$options['id'], 'nb'=>$options['nb']));
	}
	
	function cartUrl(){
		return $this->Html->url(array('plugin'=>'shop','controller'=>'shop_cart','action'=>'index'));
	}
	
	function cartLink($options = array()){
		$defaultOptions = array(
			'label' => __("Your cart (%nbItem%)",true),
			'class' => array('cart'),
		);
		if(!empty($options) && !is_array($options)){
			$options = array('label' => $options);
		}
		$opt = array_merge($defaultOptions,$options);
		$label = $opt['label'];
		if(!empty($this->params['Shop'])){
			foreach($this->params['Shop'] as $key => $val){
				if(!is_array($val)){
					$label = str_replace('%'.$key.'%',$val,$label);
				}
			}
		}
		return $this->Html->link($label,array('plugin'=>'shop','controller'=>'shop_cart','action'=>'index'),$this->O2form->normalizeAttributesOpt($opt,array('label')));
	}
	
	

}

?>