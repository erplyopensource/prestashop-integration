<?php

class Erply_Presta_Order extends Order
{
	/** @var array */
	public $_itemsAry;


	/**
	 * Get array of order items.
	 * 
	 * @return array
	 */
	public function getItems()
	{
		if(is_null($this->_itemsAry)) {
			foreach($this->getProductsDetail() as $orderDetailAry)
			{
				// Init Item
				$itemAry = $orderDetailAry;

				// Calculate prices
				// final_price is discounted price excluding tax
				$itemAry['product_price'] = Tools::ps_round($itemAry['product_price'], 2);
				$itemAry['product_final_price'] = $itemAry['product_price'];

				// Give discount by product
				if((float)$itemAry['reduction_percent'] > 0)
				{
					$itemAry['product_final_price'] -= $itemAry['product_final_price'] * $itemAry['reduction_percent'] / 100;
				}

				// Give fixed amount discount
				if((float)$itemAry['reduction_amount'] > 0)
				{
					$itemAry['product_final_price'] -= $itemAry['reduction_amount'];
				}

				// Give discount by customer group
				if($itemAry['group_reduction'])
				{
					$itemAry['product_final_price'] -= $itemAry['product_final_price'] * $itemAry['group_reduction'] / 100;
				}

				// Round.
				$itemAry['product_final_price'] = Tools::ps_round($itemAry['product_final_price'], 2);

				// Discount
				if($itemAry['product_price'] > 0) $itemAry['discount'] = (1 - $itemAry['product_final_price'] / $itemAry['product_price']) * 100;
				else $itemAry['discount'] = 0;

				// Calculate product final price with tax
				$itemAry['product_final_price_wvat'] = Tools::ps_round($itemAry['product_final_price'] * (1 + ($itemAry['tax_rate'] * 0.01)), 2) + Tools::ps_round($itemAry['ecotax'] * (1 + $itemAry['ecotax_tax_rate'] / 100), 2);

				// Calculate totals.
				$itemAry['total_net'] = Tools::ps_round(($itemAry['product_quantity'] * $itemAry['product_final_price']), 2);
				$itemAry['total_wvat'] = Tools::ps_round(($itemAry['product_quantity'] * $itemAry['product_final_price_wvat']), 2);

				$this->_itemsAry[] = $itemAry;
			}
		}
		return $this->_itemsAry;
	}
}

?>