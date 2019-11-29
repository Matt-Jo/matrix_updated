<?php
class api_ups extends ck_master_api {
	private static $access = [
		'key' => 'BBD3FF207D79AB5C',
		'user_id' => 'zboyblue',
		'password' => 'DanielisAwesome2016'
	];

	private static $account_number = 'y2e712';

	public static $local_origin = [
		'company_name' => 'CablesAndKits.com',
		'address1' => '4555 Atwater Ct',
		'address2' => 'Suite A',
		'postcode' => 30518,
		'city' => 'Buford',
		'state' => 'GA',
		'zone_id' => 19,
		'countries_id' => 223,
		'country' => 'United States',
		'countries_iso_code_2' => 'US',
		'countries_iso_code_3' => 'USA',
		'country_address_format_id' => 2,
		'telephone' => '8886220223',
	];

	public static $uses_imperial = [223];

	private static function integration() {
		$config = service_locator::get_config_service();
		return !$config->is_production();
	}

	public static function validate_address(ck_address_interface $address) {
	}

	public static function quote_rates(array $packages, ck_address_interface $to, ck_address_interface $from=NULL) {
		try {
			$address_type = new ck_address_type();
			$address_type->load('header', self::$local_origin);
			$local_origin = new ck_address2(NULL, $address_type);

			if (empty($from)) $from = $local_origin;

			// set up boilerplate
			$rate = new \Ups\Rate(self::$access['key'], self::$access['user_id'], self::$access['password'], self::integration());
			$rate_request = new \Ups\Entity\RateRequest();
			$shipment = $rate_request->getShipment();

			$pickup_type = $rate_request->getPickupType();
			$pickup_type->setCode(\Ups\Entity\PickupType::PKT_DAILY);
			$rate_request->setPickupType($pickup_type);

			$customer_classification = new \Ups\Entity\CustomerClassification();
			$customer_classification->setCode(\Ups\Entity\CustomerClassification::RT_DAILY);
			$rate_request->setCustomerClassification($customer_classification);

			$rate_information = new \Ups\Entity\RateInformation((object)['NegotiatedRatesIndicator' => 1]);
			$shipment->setRateInformation($rate_information);

			$shipment->setShipper(self::set_up_shipper($local_origin));
			$shipment->setShipFrom(self::set_up_ship_from($from));
			$shipment->setShipTo(self::set_up_ship_to($to));

			// need to deal with saturday shipping - maybe other details, check includes/modules/shipping/iux.php
			foreach ($packages as $pkg) {
				$shipment->addPackage(self::set_up_package($pkg, $from));
			}

			$rate_request->setShipment($shipment);
			//$xml = $rate->createRequest($rate_request);
			//echo htmlspecialchars($xml);
			$rate_result = $rate->shopRates($rate_request);

			$rates = [];
			foreach ($rate_result->RatedShipment as $service) {
				$rates[$service->Service->getCode()] = ['code' => $service->Service->getCode(), 'service' => $service->Service->getName(), 'list' => $service->TotalCharges->MonetaryValue, 'negotiated' => $service->NegotiatedRates->NetSummaryCharges->GrandTotal->MonetaryValue];
			}

			return $rates;
		}
		catch (CKUpsApiException $e) {
			throw $e;
		}
		catch (Exception $e) {
			throw new CKUpsApiException($e->getMessage());
		}
	}

	public static function get_label($shipment_details, array $packages, ck_address_interface $to, ck_address_interface $from=NULL, ck_address_interface $origin=NULL) {
		try {
			$address_type = new ck_address_type();
			$address_type->load('header', self::$local_origin);
			$local_origin = new ck_address2(NULL, $address_type);

			if (empty($from)) $from = $local_origin;

			// set up boilerplate
			$ship = new \Ups\Shipping(self::$access['key'], self::$access['user_id'], self::$access['password'], self::integration());
			$shipment = new \Ups\Entity\Shipment();

			$rate_information = new \Ups\Entity\RateInformation((object)['NegotiatedRatesIndicator' => 1]);
			$shipment->setRateInformation($rate_information);

			$shipment->setShipper(self::set_up_shipper($from));
			$shipment->setShipFrom(self::set_up_ship_from($from));
			$shipment->setShipTo(self::set_up_ship_to($to));
			$shipment->setSoldTo(self::set_up_sold_to($to));

			// Set service
			$service = new \Ups\Entity\Service;
			$service->setCode(self::get_service($shipment_details['service']));
			$service->setDescription($service->getName());
			$shipment->setService($service);

			/*
			// we just handle these as regular labels, passing in the customer address as $from and us as $to - if we need to use this later, here's the example code
			if ($return) {
				$returnService = new \Ups\Entity\ReturnService();
				$returnService->setCode(\Ups\Entity\ReturnService::PRINT_RETURN_LABEL_PRL);
				$shipment->setReturnService($returnService);
			}
			*/

			/*
			// this is only required for international - we'll have to figure out how we want it constructed
			// Daniel said he puts in Invoice Desc, IPN, Qty, Cost - UPS says it limits this field to 50 characters, so that's likely to cause issue with more than 1 product.
			//$shipment->setDescription('');
			*/

			foreach ($packages as $pkg) {
				$shipment->addPackage(self::set_up_package($pkg, $from));
			}

			if (!empty($shipment_details['reference_number'])) {
				$referenceNumber = new \Ups\Entity\ReferenceNumber();
				$referenceNumber->setCode($shipment_details['reference_number']['code']);
				$referenceNumber->setValue($shipment_details['reference_number']['number']);
				$shipment->setReferenceNumber($referenceNumber);
			}

			// this stuff is not normalized to the rest of the API, and appears to not be well built out.
			// hopefully it works
			$paymentInformation = new \Ups\Entity\PaymentInformation();
			if (empty($shipment_details['account_number'])) {
				$billTo = new \Ups\Entity\BillShipper();
				$billTo->setAccountNumber(self::$account_number);
				$prepaid = new \Ups\Entity\Prepaid();
				$prepaid->setBillShipper($billTo);
				$paymentInformation->setPrepaid($prepaid);
			}
			else {
				if (!empty($origin)) {
					$billTo = new \Ups\Entity\BillThirdParty();
					$billTo->setAccountNumber($shipment_details['account_number']);
					$billTo->setThirdPartyAddress(self::set_up_address($origin));
					$paymentInformation->setBillThirdParty($billTo);
				}
				elseif (!empty($from)) {
					$billTo = new \Ups\Entity\BillThirdParty();
					$billTo->setAccountNumber($shipment_details['account_number']);
					$billTo->setThirdPartyAddress(self::set_up_address($from));
					$paymentInformation->setBillThirdParty($billTo);
				}
				else {
					$billTo = new \Ups\Entity\FreightCollect();
					$billTo->setAccountNumber($shipment_details['account_number']);
					$billTo->setBillReceiverAddress(self::set_up_address($to));
					$paymentInformation->setFreightCollect($billTo);
				}
			}
			$shipment->setPaymentInformation($paymentInformation);

			$labels = [];

			if (($confirm = $ship->confirm(\Ups\Shipping::REQ_VALIDATE, $shipment)) && $confirm->Response->ResponseStatusCode == 1) {
				if ($accept = $ship->accept($confirm->ShipmentDigest)) {
					$labels['list'] = $accept->ShipmentCharges->TotalCharges->MonetaryValue;
					$labels['negotiated'] = $accept->NegotiatedRates->NetSummaryCharges->GrandTotal->MonetaryValue;
					$labels['master_tracking_number'] = $accept->ShipmentIdentificationNumber;

					$pkgs = is_array($accept->PackageResults)?$accept->PackageResults:[$accept->PackageResults];

					$labels['packages'] = [];
					foreach ($pkgs as $pkg) {
						$labels['packages'][] = [
							'tracking_number' => $pkg->TrackingNumber,
							'image_format' => $pkg->LabelImage->LabelImageFormat->Code,
							'image' => $pkg->LabelImage->GraphicImage,
							'html_image' => $pkg->LabelImage->HTMLImage
						];
					}
				}
			}
			else {
				ob_start();
				var_dump($confirm);
				$msg = ob_get_clean();
				throw new CKUpsApiException('Could not create UPS Label: '.$msg);
			}

			return $labels;
		}
		catch (CKUpsApiException $e) {
			throw $e;
		}
		catch (Exception $e) {
			throw new CKUpsApiException($e->getMessage());
		}
	}

	public static function void_label($tracking_number) {
		try {
			$ship = new \Ups\Shipping(self::$access['key'], self::$access['user_id'], self::$access['password'], self::integration());
			$void = $ship->void($tracking_number);
			var_dump($void);

			return $something;
		} catch (Exception $e) {
			throw new CKUpsApiException($e->getMessage());
		}
	}

	public static function track($tracking_number) {
	}

	private static $tt_to_sh_services = [];

	private static function build_time_in_transit_service_map() {
		self::$tt_to_sh_services[\Ups\Entity\Service::TT_S_US_AIR_1DAYAM] = \Ups\Entity\Service::S_AIR_1DAYEARLYAM;
		self::$tt_to_sh_services[\Ups\Entity\Service::TT_S_US_AIR_1DAY] = \Ups\Entity\Service::S_AIR_1DAY;
		self::$tt_to_sh_services[\Ups\Entity\Service::TT_S_US_AIR_SAVER] = \Ups\Entity\Service::S_AIR_1DAYSAVER;
		self::$tt_to_sh_services[\Ups\Entity\Service::TT_S_US_AIR_2DAYAM] = \Ups\Entity\Service::S_AIR_2DAYAM;
		self::$tt_to_sh_services[\Ups\Entity\Service::TT_S_US_AIR_2DAY] = \Ups\Entity\Service::S_AIR_2DAY;
		self::$tt_to_sh_services[\Ups\Entity\Service::TT_S_US_3DAYSELECT] = \Ups\Entity\Service::S_3DAYSELECT;
		self::$tt_to_sh_services[\Ups\Entity\Service::TT_S_US_GROUND] = \Ups\Entity\Service::S_GROUND;
		self::$tt_to_sh_services[\Ups\Entity\Service::TT_S_US_INTL_STANDARD] = \Ups\Entity\Service::S_STANDARD;
		self::$tt_to_sh_services[\Ups\Entity\Service::TT_S_US_INTL_EXPRESS] = \Ups\Entity\Service::S_WW_EXPRESS;
		self::$tt_to_sh_services[\Ups\Entity\Service::TT_S_US_INTL_EXPRESSPLUS] = \Ups\Entity\Service::S_WW_EXPRESSPLUS;
		self::$tt_to_sh_services[\Ups\Entity\Service::TT_S_US_INTL_EXPEDITED] = \Ups\Entity\Service::S_WW_EXPEDITED;
		self::$tt_to_sh_services[\Ups\Entity\Service::TT_S_US_INTL_SAVER] = \Ups\Entity\Service::S_SAVER;

		self::$tt_to_sh_services[\Ups\Entity\Service::TT_S_US_AIR_1DAYSATAM] = \Ups\Entity\Service::S_AIR_1DAYEARLYAM;
		self::$tt_to_sh_services[\Ups\Entity\Service::TT_S_US_AIR_1DAYSAT] = \Ups\Entity\Service::S_AIR_1DAY;
		self::$tt_to_sh_services[\Ups\Entity\Service::TT_S_US_AIR_2DAYSAT] = \Ups\Entity\Service::S_AIR_2DAY;
	}

	public static function transit_time($shipment_details, ck_address_interface $to, ck_address_interface $from=NULL) {
		try {
			$address_type = new ck_address_type();
			$address_type->load('header', self::$local_origin);
			$local_origin = new ck_address2(NULL, $address_type);

			if (empty($from)) $from = $local_origin;

			// set up boilerplate
			$timeInTransit = new \Ups\TimeInTransit(self::$access['key'], self::$access['user_id'], self::$access['password'], self::integration());
			$request = new \Ups\Entity\TimeInTransitRequest;

			// Addresses
			$request->setTransitFrom(self::set_up_address_artifact($from));
			$request->setTransitTo(self::set_up_address_artifact($to));

			if (in_array($from->get_header('countries_id'), self::$uses_imperial)) $uom_weight = \Ups\Entity\UnitOfMeasurement::UOM_LBS;
			else {
				$uom_weight = \Ups\Entity\UnitOfMeasurement::UOM_KGS;
				$shipment_details['weight'] = unit_conversion::lbs2kg($shipment_details['weight']);
			}

			// Weight
			$shipmentWeight = new \Ups\Entity\ShipmentWeight;
			$shipmentWeight->setWeight($shipment_details['weight']);
			$unit = new \Ups\Entity\UnitOfMeasurement;
			$unit->setCode($uom_weight);
			$shipmentWeight->setUnitOfMeasurement($unit);
			$request->setShipmentWeight($shipmentWeight);

			// Packages
			$request->setTotalPackagesInShipment($shipment_details['package_count']);

			// InvoiceLines
			$invoiceLineTotal = new \Ups\Entity\InvoiceLineTotal;
			$invoiceLineTotal->setMonetaryValue($shipment_details['total_value']);
			$invoiceLineTotal->setCurrencyCode('USD');
			$request->setInvoiceLineTotal($invoiceLineTotal);

			// Pickup date
			$request->setPickupDate(new DateTime);

			// Get data
			$time_result = $timeInTransit->getTimeInTransit($request);

			self::build_time_in_transit_service_map();

			$times = [];
			foreach ($time_result->ServiceSummary as $service) {
				if (!empty(self::$tt_to_sh_services[$service->Service->getCode()])) {
					$service_code = self::$tt_to_sh_services[$service->Service->getCode()];
					$key = $service_code;
				}
				else {
					$service_code = '';
					$key = $service->Service->getDescription();
				}

				$times[$key] = ['transit_code' => $service->Service->getCode(), 'service_code' => $service_code, 'service' => $service->Service->getDescription(), 'guaranteed' => $service->Guaranteed->Code==\Ups\Entity\Guaranteed::G_YES, 'transit_days' => $service->EstimatedArrival->BusinessTransitDays, 'arrival_date' => $service->EstimatedArrival->Date, 'arrival_weekday' => $service->EstimatedArrival->DayOfWeek];
			}

			return $times;
		}
		catch (CKUpsApiException $e) {
			throw $e;
		}
		catch (Exception $e) {
			throw new CKUpsApiException($e->getMessage());
		}
	}

	private static function set_up_shipper(ck_address_interface $address, $account_number=NULL) {
		try {
			if (empty($account_number)) $account_number = self::$account_number;

			$shipper = new \Ups\Entity\Shipper();
			$shipper->setShipperNumber($account_number);
			$shipper->setAddress(self::set_up_address($address));
			if ($address->has_company_name() && $address->has_name()) {
				$shipper->setName(htmlspecialchars($address->get_company_name()));
				$shipper->setAttentionName(htmlspecialchars($address->get_name()));
			}
			elseif ($address->has_company_name()) $shipper->setName(htmlspecialchars($address->get_company_name()));
			elseif ($address->has_name()) $shipper->setName(htmlspecialchars($address->get_name()));
			if (!empty($address->get_header('telephone'))) $shipper->setPhoneNumber($address->get_header('telephone'));

			return $shipper;
		}
		catch (CKUpsApiException $e) {
			throw $e;
		}
		catch (Exception $e) {
			throw new CKUpsApiException('Failed to set up shipper for UPS call: ['.$e->getMessage().']');
		}
	}

	private static function set_up_ship_from(ck_address_interface $address) {
		try {
			$shipFrom = new \Ups\Entity\ShipFrom();
			$shipFrom->setAddress(self::set_up_address($address));
			if ($address->has_company_name() && $address->has_name()) {
				$shipFrom->setName(htmlspecialchars($address->get_company_name()));
				$shipFrom->setAttentionName(htmlspecialchars($address->get_name()));
			}
			elseif ($address->has_company_name()) $shipFrom->setName(htmlspecialchars($address->get_company_name()));
			elseif ($address->has_name()) $shipFrom->setName(htmlspecialchars($address->get_name()));
			if (!empty($address->has_company_name())) $shipFrom->setCompanyName(htmlspecialchars($address->get_company_name()));
			elseif(!empty($address->has_name())) $shipFrom->setCompanyName(htmlspecialchars($address->has_name()));
			else $shipFrom->setCompanyName(htmlspecialchars($address->get_header('address1')));
			if (!empty($address->get_header('telephone'))) $shipFrom->setPhoneNumber($address->get_header('telephone'));

			return $shipFrom;
		}
		catch (CKUpsApiException $e) {
			throw $e;
		}
		catch (Exception $e) {
			throw new CKUpsApiException('Failed to set up ship from for UPS call: ['.$e->getMessage().']');
		}
	}

	private static function set_up_ship_to(ck_address_interface $address) {
		try {
			$shipTo = new \Ups\Entity\ShipTo();
			$shipTo->setAddress(self::set_up_address($address));
			/*if ($address->has_company_name() && $address->has_name()) {
				$shipTo->setName(htmlspecialchars($address->get_company_name()));
				$shipTo->setAttentionName(htmlspecialchars($address->get_name()));
			}
			elseif ($address->has_company_name()) $shipTo->setName(htmlspecialchars($address->get_company_name()));
			elseif ($address->has_name()) $shipTo->setName(htmlspecialchars($address->get_name()));*/
			if (!empty($address->has_company_name())) $shipTo->setCompanyName(htmlspecialchars($address->get_company_name()));
			if (!empty($address->has_name())) $shipTo->setAttentionName(htmlspecialchars($address->get_name()));
			if (!empty($address->get_header('telephone'))) $shipTo->setPhoneNumber($address->get_header('telephone'));

			return $shipTo;
		}
		catch (CKUpsApiException $e) {
			throw $e;
		}
		catch (Exception $e) {
			throw new CKUpsApiException('Failed to set up ship to for UPS call: ['.$e->getMessage().']');
		}
	}

	private static function set_up_sold_to(ck_address_interface $address) {
		try {
			$soldTo = new \Ups\Entity\SoldTo;
			$soldTo->setAddress(self::set_up_address($address));
			/*if ($address->has_company_name() && $address->has_name()) {
				$soldTo->setName(htmlspecialchars($address->get_company_name()));
				$soldTo->setAttentionName(htmlspecialchars($address->get_name()));
			}
			elseif ($address->has_company_name()) $soldTo->setName(htmlspecialchars($address->get_company_name()));
			elseif ($address->has_name()) $soldTo->setName(htmlspecialchars($address->get_name()));*/
			if (!empty($address->has_company_name())) $soldTo->setCompanyName(htmlspecialchars($address->get_company_name()));
			if (!empty($address->has_name())) $soldTo->setAttentionName(htmlspecialchars($address->get_name()));
			if (!empty($address->get_header('telephone'))) $soldTo->setPhoneNumber($address->get_header('telephone'));

			return $soldTo;
		}
		catch (CKUpsApiException $e) {
			throw $e;
		}
		catch (Exception $e) {
			throw new CKUpsApiException('Failed to set up sold to for UPS call: ['.$e->getMessage().']');
		}
	}

	private static function set_up_address(ck_address_interface $address) {
		try {
			$ups_address = new \Ups\Entity\Address;
			$ups_address->setAddressLine1($address->get_header('address1'));
			if (!empty($address->get_header('address2'))) $ups_address->setAddressLine2($address->get_header('address2'));
			$ups_address->setCity(htmlspecialchars($address->get_header('city')));
			$ups_address->setStateProvinceCode($address->get_state());
			$ups_address->setPostalCode($address->get_header('postcode'));
			$ups_address->setCountryCode($address->get_header('countries_iso_code_2'));

			return $ups_address;
		} catch (Exception $e) {
			throw new CKUpsApiException('Failed to set up address for UPS call: ['.$e->getMessage().']');
		}
	}

	private static function set_up_address_artifact(ck_address_interface $address) {
		try {
			$ups_address = new \Ups\Entity\AddressArtifactFormat;
			$ups_address->setPoliticalDivision3($address->get_header('country'));
			$ups_address->setPostcodePrimaryLow($address->get_header('postcode'));
			$ups_address->setCountryCode($address->get_header('countries_iso_code_2'));

			return $ups_address;
		} catch (Exception $e) {
			throw new CKUpsApiException('Failed to set up address for UPS call: ['.$e->getMessage().']');
		}
	}

	private static function set_up_package($package, ck_address_interface $from=NULL) {
		try {
			if (in_array($from->get_header('countries_id'), self::$uses_imperial)) {
				$uom_weight = \Ups\Entity\UnitOfMeasurement::UOM_LBS;
				$uom_dim = \Ups\Entity\UnitOfMeasurement::UOM_IN;
			}
			else {
				$uom_weight = \Ups\Entity\UnitOfMeasurement::UOM_KGS;
				$uom_dim = \Ups\Entity\UnitOfMeasurement::UOM_CM;

				$package['weight'] = unit_conversion::lbs2kg($package['weight']);
				if (!empty($package['dim'])) {
					$package['dim']['height'] = unit_conversion::in2cm($package['dim']['height']);
					$package['dim']['width'] = unit_conversion::in2cm($package['dim']['width']);
					$package['dim']['length'] = unit_conversion::in2cm($package['dim']['length']);
				}
			}

			$ups_package = new \Ups\Entity\Package();
			$ups_package->getPackagingType()->setCode(\Ups\Entity\PackagingType::PT_PACKAGE);
			$ups_package->getPackageWeight()->setWeight($package['weight']);

			// if you need this (depends of the shipper country)
			$weightUnit = new \Ups\Entity\UnitOfMeasurement();
			$weightUnit->setCode($uom_weight);
			$ups_package->getPackageWeight()->setUnitOfMeasurement($weightUnit);

			if (!empty($package['dim'])) {
				$dimensions = new \Ups\Entity\Dimensions();
				$dimensions->setHeight($package['dim']['height']);
				$dimensions->setWidth($package['dim']['width']);
				$dimensions->setLength($package['dim']['length']);

				$dimUnit = new \Ups\Entity\UnitOfMeasurement();
				$dimUnit->setCode($uom_dim);
				$dimensions->setUnitOfMeasurement($dimUnit);

				$ups_package->setDimensions($dimensions);
			}

			if (!empty($package['reference_number'])) {
				$referenceNumber = new \Ups\Entity\ReferenceNumber();
				$referenceNumber->setCode($package['reference_number']['code']);
				$referenceNumber->setValue($package['reference_number']['number']);
				$ups_package->setReferenceNumber($referenceNumber);
			}

			// I don't think we actually insure anything, this appears to be a holdover from the old UPS rate quoting - I'll set it up but comment it out
			//$packageServiceOptions = new \Ups\Entity\PackageServiceOptions((object)['InsuredValue' => (object)['CurrencyCode' => 'USD', 'MonetaryValue' => 100]]);
			//$ups_package->setPackageServiceOptions($packageServiceOptions);

			return $ups_package;
		} catch (Exception $e) {
			throw new CKUpsApiException('Failed to set up package for UPS call: ['.$e->getMessage().']');
		}
	}

	private static function get_service($service) {
		$services = \Ups\Entity\Service::getServices();

		if (is_numeric($service) && isset($services[$service])) return $service;
		elseif (!is_numeric($service) && defined('\Ups\Entity\Service::'.$service)) return constant('\Ups\Entity\Service::'.$service);

		throw new CKUpsApiException('Could not validate selected service type: ['.$service.']');
	}

	// paperless document API
	// tracking API
}

class CKUpsApiException extends CKApiException {
}
?>
