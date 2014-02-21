<?php
/**
 * TouchNet uPay Connector Class
 */

namespace DF\Service;

class TouchNet
{
	// Creates a new transaction in the local database and returns the data necessary to create the form post.
	public static function createTransaction($amount, $user, $site_id = null)
	{
		$record = new \Entity\Touchnet();
		$record->user = $user;
		$record->time_created = time();
		$record->time_updated = time();
		$record->payment_status = 'pending';
		$record->save();
		
		$record_id = $record->id;
		$trans_id = self::getTransactionId($record_id);
		
		$record->transaction_id = $trans_id;
		$record->save();
		
		$settings = self::getSettings();
		
		// Compose the transaction information array and return it.
		$transaction_data = array(
			'record'	=> $record,
			'url'		=> $settings->form_url,
			'fields'	=> array(
				'UPAY_SITE_ID'		=> (!is_null($site_id)) ? $site_id : $settings->site_id,
				'EXT_TRANS_ID'		=> $trans_id,
				'EXT_TRANS_ID_LABEL' => $settings->trans_id_label,
				'AMT'				=> number_format($amount, 2, '.', ''),
				'VALIDATION_KEY'	=> self::getValidationKey($amount, $trans_id),
			),
		);
		return $transaction_data;
	}

	// Get the validation key for a transaction.
	public static function getValidationKey($amount, $trans_id)
	{
		$settings = self::getSettings();
		$encode_string = $settings->posting_key . $trans_id . number_format($amount, 2, '.', '');
		
		return base64_encode(md5($encode_string, true));
	}
	
	// Generate a new transaction ID.
	public static function getTransactionId($record_id)
	{
		$settings = self::getSettings();
		
		$record_hash = strtoupper(md5($settings->posting_key.$record_id));
		$record_id_string = str_pad($record_id, 6, '0', STR_PAD_LEFT);
		
		return substr($record_hash, 0, 5).$record_id_string;
	}
	
	// Handle the submission of posting URL data from the payment system. Returns the UserID to mark as paid if applicable.
	public static function processPostData()
	{
		$settings = self::getSettings();
		
		// Check for valid posting key.
		if (strcmp($_REQUEST['posting_key'], $settings->posting_key) == 0)
		{
			// Check for existing transaction ID.
			$trans_id = $_REQUEST['EXT_TRANS_ID'];
			
			$record = \Entity\Touchnet::getRepository()->findOneBy(array('transaction_id' => $trans_id));
			
			if ($record instanceof \Entity\Touchnet)
			{
				if ($_REQUEST['pmt_status'] == "success")
				{
					$record->payment_status = 'success';
					$record->payment_amount = floatval($_REQUEST['pmt_amt']);
					$record->payment_card_type = (string)$_REQUEST['card_type'];
					$record->payment_name = (string)$_REQUEST['name_on_acct'];
					$record->payment_order_id = intval($_REQUEST['sys_tracking_id']);
					$record->payment_internal_trans_id = $_REQUEST['tpg_trans_id'];
					$record->time_updated = time();
					$record->save();
					
					return $record;
				}
				else if ($_REQUEST['pmt_status'] == "cancelled")
				{
					$record->payment_status = 'cancelled';
					$record->time_updated = time();
					$record->save();
					
					return NULL;
				}
				else
				{
					self::error('Invalid status code provided.');
				}
			}
			else
			{
				self::error('Transaction not found!');
			}
		}
		else
		{
			self::error('Invalid posting key specified!');
		}
	}
	
	public static function error($text)
	{
		file_put_contents(DF_INCLUDE_WEsB.'/touchnet.txt', $text, FILE_APPEND);
		throw new \DF\Exception($text);
	}
	
	public static function getSettings()
	{
		static $settings;
		if (!$settings)
		{
			$config = \Zend_Registry::get('config');
			$settings = $config->services->touchnet;
		}
		return $settings;
	}
}