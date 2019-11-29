<?php
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function testMailServiceIsAccessibleFromServiceLocator(): void
    {
        $mailer = service_locator::get_mail_service();
        $this->assertInstanceOf(
            mail_service_interface::class,
            $mailer
        );
    }

    public function testEmailWithNoSubjectIsInvalid(): void
    {
        $this->expectException(mail_service_exception::class);
        $mailer = service_locator::get_mail_service();
        $mail = $mailer->create_mail()
            ->set_from("sales@cablesandkits.com")
            ->add_to("success@simulator.amazonses.com")
            ->set_body("test")
            ;
        $mailer->send($mail);
    }


    public function testSendingValidMailWithAttachment(): void
    {
        $mailer = service_locator::get_mail_service();
        $mail = $mailer->create_mail()
            ->set_subject('Inquiry from CablesAndKits.com '.time())
            ->add_to('guilledevel@gmail.com')
            ->set_from("sales@cablesandkits.com")
            ->set_body(null, "hola perinola!")
            ->create_attachment(
                file_get_contents(__DIR__.'/test.csv'), 
                'application/octet-stream', 
                'attachment', 
                'base64', 
                'test.csv'
            )
            ;
        $mailer->send($mail);
    }


    public function testAddressValidationWasCorrectlyPorted(): void
    {
        $this->assertEquals(
            $this->_legacy_email_is_valid("success@simulator.amazonses.com"),
            service_locator::get_mail_service()::validate_address("success@simulator.amazonses.com")
        );

        $this->assertEquals(
            $this->_legacy_email_is_valid("wrong@"),
            service_locator::get_mail_service()::validate_address("wrong@")
        );

        $this->assertEquals(
            $this->_legacy_email_is_valid("still@wrong"),
            service_locator::get_mail_service()::validate_address("still@wrong")
        );
    }

    /**
     * Function extracted from ck_email class
     *
     * @param [type] $email
     * @return void
     */
    private function _legacy_email_is_valid($email) {
		$valid_address = true;

		$mail_pat = '^(.+)@(.+)$';
		$valid_chars = "[^] \(\)<>@,;:\.\\\"\[]";
		$atom = "$valid_chars+";
		$quoted_user='(\"[^\"]*\")';
		$word = "($atom|$quoted_user)";
		$user_pat = "^$word(\.$word)*$";
		$ip_domain_pat='^\[([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\]$';
		$domain_pat = "^$atom(\.$atom)*$";

		if (preg_match("/$mail_pat/", $email, $components)) {
			$user = $components[1];
			$domain = $components[2];
			// validate user
			if (preg_match("/$user_pat/", $user)) {
				// validate domain
				if (preg_match("/$ip_domain_pat/", $domain, $ip_components)) {
					// this is an IP address
					for ($i=1;$i<=4;$i++) {
						if ($ip_components[$i] > 255) {
							$valid_address = false;
							break;
						}
					}
				}
				else {
					// Domain is a name, not an IP
					if (preg_match("/$domain_pat/", $domain)) {
						/* domain name seems valid, but now make sure that it ends in a valid TLD or ccTLD
						and that there's a hostname preceding the domain or country. */
						$domain_components = explode(".", $domain);
						// Make sure there's a host name preceding the domain.
						if (sizeof($domain_components) < 2) {
							$valid_address = false;
						}
						else {
							$top_level_domain = strtolower($domain_components[sizeof($domain_components)-1]);
							// Allow all 2-letter TLDs (ccTLDs)
							if (preg_match('/^[a-z][a-z]$/', $top_level_domain) != 1) {
								$tld_pattern = '';
								// Get authorized TLDs from text file
								$tlds = file(DIR_WS_INCLUDES.'tld.txt');
								foreach($tlds as $line) {
									// Get rid of comments
									$words = explode('#', $line);
									$tld = trim($words[0]);
									// TLDs should be 3 letters or more
									if (preg_match('/^[a-z]{3,}$/', $tld) == 1) {
										$tld_pattern .= '^'.$tld.'$|';
									}
								}
								// Remove last '|'
								$tld_pattern = substr($tld_pattern, 0, -1);
								if (preg_match("/$tld_pattern/", $top_level_domain) == 0) {
									$valid_address = false;
								}
							}
						}
					}
					else {
						$valid_address = false;
					}
				}
			}
			else {
				$valid_address = false;
			}
		}
		else {
			$valid_address = false;
		}
		if ($valid_address && ENTRY_EMAIL_ADDRESS_CHECK == 'true') {
			if (!checkdnsrr($domain, "MX") && !checkdnsrr($domain, "A")) {
				$valid_address = false;
			}
		}
		return $valid_address;
	}
}



