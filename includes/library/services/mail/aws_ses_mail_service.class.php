<?php
use PHPMailer\PHPMailer\PHPMailer;
use Aws\Ses\SesClient;
use Aws\Ses\Exception\SesException;
use Aws\Credentials\Credentials;

/**
 * E-Mail manager
 */
class aws_ses_mail_service implements mail_service_interface {
    use ck_singleton_trait;


    private $vendor;

    private function __construct($params) {
        $this->vendor = new SesClient([
            'version' => 'latest',
            'region'  =>  "us-east-1",
            'credentials' => [
                'key'    => $params['aws_access_key_id'],
                'secret' => $params['aws_secret_access_key'],
            ],
        ]);
    }

    /**
     * Factory method
     *
     * @return mail_interface
     */
    public function create_mail(): mail_interface {
        $mail = new mail();
        // $mail->add_custom_header('X-SES-CONFIGURATION-SET', 'ConfigSet');
        return $mail;
    }

    /**
     * Send the given mail
     *
     * @param mail_interface $mail
     * @return void
     */
    public function send(mail_interface $mail): bool {
        $this->assert_is_ready($mail);
        $char_set = 'UTF-8';
		
		$email = [
			'Destination' => [
				'ToAddresses'   => array_map([$this,'recipient_to_string'], $mail->get_to()),
			],
			'ReplyToAddresses' => $mail->get_reply_to(),
			'Source' => $mail->get_from(),
			// If you aren't using a configuration set, comment or delete the
            // following line
            //  'ConfigurationSetName' => $configuration_set,
		];

		if ($cc = $mail->get_cc()) {
			$email['Destination']['CcAddresses'] = array_map([$this,'recipient_to_string'], $cc);
		}

		if ($bcc = $mail->get_bcc()) {
			$email['Destination']['BccAddresses'] = array_map([$this,'recipient_to_string'], $bcc);
		}
        
		if($mail->has_attachments()) {
            $message = $mail->get_raw_message();

			$email = [];

			$email['RawMessage'] = ['Data' => $message];

			try {
	            $result = $this->vendor->sendRawEmail($email);
			}
			catch (SesException $e) {
			}
            $messageId = $result->get('MessageId');
        } else {
			$email['Message'] = [
				'Body' => [
					'Html' => [
						'Charset' => $char_set,
						'Data' => $mail->get_html_body(),
					],
					'Text' => [
						'Charset' => $char_set,
						'Data' => $mail->get_text_body(),
					],
				],
				'Subject' => [
					'Charset' => $char_set,
					'Data' => $mail->get_subject(),
				],
            ];
            $result = $this->vendor->sendEmail($email);
            $messageId = $result['MessageId'];
        }
        // $this->log("Email sent! Message ID: $messageId"."\n");

		return TRUE;
    }

    /**
     * Return TRUE if $mail is a valid email address
     *
     * @param string $mail
     * @return boolean
     */
    public static function validate_address(string $mail): bool {
        return boolval(filter_var($mail,FILTER_VALIDATE_EMAIL));
    }

    /**
     * Validates minimum preconditions for an email to be ready to be sent
     *
     * @param mail_interface $mail
     * @throws mail_service_exception
     */
    private function assert_is_ready(mail_interface $mail): void {
        if(empty($mail->get_to())){
            throw new mail_service_exception("'To' field is required");
        }
        if(empty($mail->get_from())){
            throw new mail_service_exception("'From' field is required");
        }
        if(empty($mail->get_subject())){
            throw new mail_service_exception("'Subject' field is required");
        }
    }

    private function assert_priority_is_valid(int $priority) {
        if(!in_array($priority, self::PRIORITY_LOW, self::PRIORITY_NORMAL, self::PRIORITY_HIGH, true)) {
            throw new mail_service_exception(
                "Expected priority is ".
                "self::PRIORITY_LOW, self::PRIORITY_NORMAL, or self::PRIORITY_HIGH".
                " got \"".$priority."\" instead"
            );
        }
    }

    private function recipient_to_string(iterable $recipient): string {
        return $recipient[0];
    }

}

