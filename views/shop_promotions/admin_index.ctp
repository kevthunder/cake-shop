<div class="shopPromotions index">
	<?php
		echo $this->Form->create('Shop Promotion', array('class' => 'search', 'url' => array('action' => 'index')));
		echo $this->Form->input('q', array('class' => 'keyword', 'label' => false, 'after' => $form->submit(__('Search', true), array('div' => false))));
		echo $this->Form->end();
	?>	
	<h2><?php __('Shop Promotions');?></h2>
	
	<table cellpadding="0" cellspacing="0">
		<tr>
			<th><?php echo $this->Paginator->sort('id');?></th>			
			<th><?php echo $this->Paginator->sort('code');?></th>			
			<th><?php echo $this->Paginator->sort('title_fre');?></th>			
			<th><?php echo $this->Paginator->sort('title_eng');?></th>			
			<th><?php echo $this->Paginator->sort('desc_fre');?></th>			
			<th><?php echo $this->Paginator->sort('desc_eng');?></th>			
			<th><?php echo $this->Paginator->sort('operator');?></th>		
			<th><?php echo $this->Paginator->sort('val');?></th>			
			<th><?php __('Uses');?></th>
			<th class="actions"><?php __('Actions');?></th>
		</tr>
		<?php
			$i = 0;
			$bool = array(__('No', true), __('Yes', true));
			foreach ($shopPromotions as $shopPromotion) {
				$class = null;
				if ($i++ % 2 == 0) {
					$class = ' class="altrow"';
				}
				?>
					<tr<?php echo $class;?>>
						<td class="id"><?php echo $shopPromotion['ShopPromotion']['id']; ?>&nbsp;</td>
						<td class="code"><?php echo $shopPromotion['ShopPromotion']['code']; ?>&nbsp;</td>
						<td class="title_fre"><?php echo $shopPromotion['ShopPromotion']['title_fre']; ?>&nbsp;</td>
						<td class="title_eng"><?php echo $shopPromotion['ShopPromotion']['title_eng']; ?>&nbsp;</td>
						<td class="desc_fre"><?php echo $text->truncate($shopPromotion['ShopPromotion']['desc_fre'], 150, array('exact' => false)); ?>&nbsp;</td>
						<td class="desc_eng"><?php echo $text->truncate($shopPromotion['ShopPromotion']['desc_eng'], 150, array('exact' => false)); ?>&nbsp;</td>
						<td class="operator"><?php echo $operators[$shopPromotion['ShopPromotion']['operator']]['label']; ?>&nbsp;</td>
						<td class="val"><?php echo $shopPromotion['ShopPromotion']['val']; ?>&nbsp;</td>
						<td class="uses"><?php echo $shopPromotion['ShopPromotion']['uses']; ?>&nbsp;</td>
						<td class="actions">
							<?php 
							if( $shopPromotion['ShopPromotion']['coupon_code_needed'] ) { 
								echo $this->Html->link(__('View Coupons', true), array('plugin'=>'shop','controller'=>'shop_coupons','action' => 'index', $shopPromotion['ShopPromotion']['id']), array('class' => 'view'));
							} ?>
							<?php echo $this->Html->link(__('Edit', true), array('action' => 'edit', $shopPromotion['ShopPromotion']['id']), array('class' => 'edit')); ?>
							<?php echo $this->Html->link(__('Delete', true), array('action' => 'delete', $shopPromotion['ShopPromotion']['id']), array('class' => 'delete'), sprintf(__('Are you sure you want to delete # %s?', true), $shopPromotion['ShopPromotion']['id'])); ?>
						</td>
					</tr>
				<?php
			}
		?>
	</table>
	
	<p class="paging">
		<?php
			echo $this->Paginator->counter(array(
				'format' => __('Page %page% of %pages%, showing %current% records out of %count% total, starting on record %start%, ending on %end%', true)
			));
		?>	</p>

	<div class="paging">
		<?php echo $this->Paginator->prev('« '.__('previous', true), array(), null, array('class'=>'disabled'));?>
 |
		<?php echo $this->Paginator->numbers();?>
 |
		<?php echo $this->Paginator->next(__('next', true).' »', array(), null, array('class' => 'disabled'));?>
	</div>
</div>
<div class="actions">
	<ul>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Shop Promotion', true)), array('action' => 'add')); ?></li>
	</ul>
</div>