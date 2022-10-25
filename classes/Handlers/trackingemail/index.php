<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>trackingemail</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
</head>
	<body style="margin: 0; padding: 0; font-family: Arial; background-color: #f2f2f2;">
		<table align="center" border="0" cellpadding="25px" cellspacing="0" width="100%" bgcolor="#fff" style="border:15px solid #f2f2f2; max-width:600px; margin:0 auto; font-size: 14px; line-height:20px;">
			<tr>
				<td>
					
					<table align="center" border="0" cellpadding="20px" cellspacing="0">
						<tr>
							<td colspan="2" align="center">
								<img src="https://www.dpd.com/nl/wp-content/themes/DPD_NoLogin/images/DPD_logo_redgrad_rgb_responsive.svg" class="size-icon-logo" alt="dpd logo" width="119px" height="auto">
							</td>
						</tr>
						<tr>
							<td colspan="2" style="font-size: 24px; ">
								<?=sprintf(__("Je pakket van bestelling #%s wordt verstuurd",'dpdconnect'),$orderId);?>
							</td>
						</tr>
						<tr>
							<td colspan="2">
								<?=sprintf(__("Hallo %s",'dpdconnect'),$data['shipment']['receiver']['name1']);?>,<br/>
								<?=__("We hebben je pakket zojuist verzonden. Je pakket wordt bij DPD aangeboden.",'dpdconnect');?>
							</td>
						</tr>
						<tr>
							<td valign="top">
								<strong style="color:#EF0326"><?=__("Bezorgadres",'dpdconnect');?></strong>
                                <?php if($data['shipmentType'] == 'parcelshop') { ?>
                                    <p>
                                   <?=__("Parcelshop",'dpdconnect');?>
                                    </p>
                                <?php } else { ?>
								<p>
                                    <?= $data['shipment']['receiver']['street']; ?><br/>
                                    <?= isset($data['shipment']['receiver']['postalCode']) ? $data['shipment']['receiver']['postalCode'] : $data['shipment']['receiver']['postalcode'] ?> <?=$data['shipment']['receiver']['city']; ?>
								</p>
                                <?php } ?>
							</td>
							<td valign="top" style="border-left: 1px solid #ddd;">
								<div >
									<strong style="color:black"><?=__("Afzender",'dpdconnect');?></strong>
									<p>
                                        <?=$data['shipment']['sender']['name1']; ?>
									</p>
									<br/>
									<strong style="color:black"><?=__("Barcode",'dpdconnect');?></strong>
<?php foreach($data['parcelNumbers'] as $parcelNumber) { ?>
                                    <p style="margin-bottom:0;">
                                        <?=$parcelNumber;?>
                                    </p>
<?php } ?>
								</div>
							</td>
						</tr>
						<tr>
							<td colspan="2" style="font-size: 24px; ">
								&nbsp;
							</td>
						</tr>
						<tr>
							<td colspan="2" bgcolor="#f2f2f2">
								<div style="font-size: 24px;"><?=__("Volg je pakket",'dpdconnect');?></div>
								<p>
                                    <?=__("Klik op onderstaande link om je pakket te volgen",'dpdconnect');?>
								</p>
<?php foreach($data['parcelNumbers'] as $parcelNumber) { ?>
								<p>
									<a href="https://www.dpdgroup.com/nl/mydpd/my-parcels/track?lang=en&parcelNumber=<?=$parcelNumber; ?>" style=" display:inline-block; text-decoration:none; width:140px; height:48px; color:#fff; background-color: #EF0326; text-align: center; line-height: 48px; font-weight: bold;"><?=__("Volg pakket",'dpdconnect');?></a>
								</p>
<?php } ?>
							</td>
						</tr>
						<tr>
							<td colspan="2">
                                <?=__("Met vriendelijke groet,",'dpdconnect');?>
								<br/>
								Team DPD
							</td>
						</tr>
					</table>
					
				</td>
			</tr>
		</table>
	</body>
</html>