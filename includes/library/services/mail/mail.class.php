<?php 
use PHPMailer\PHPMailer\PHPMailer;


/**
 * An email
 */
class mail implements mail_interface {
    
    public $mail;

    public function __construct() {
        $this->mail = new PHPMailer();
    }

    public function set_body(?string $html = null, ?string $text = null): mail_interface {
        $this->mail->msgHTML($html ?? $text);
        if($text != null) {
            $this->mail->AltBody = $text;
        }
        return $this;
    }

    /**
     * Creates a mime part attachment
     *
     * Attachment is automatically added to the mail object after creation. The
     * attachment object is returned to allow for further manipulation.
     */
    public function create_attachment(string $body, ?string $filename = null, ?string $mimeType = self::TYPE_OCTETSTREAM, ?string $disposition = self::DISPOSITION_ATTACHMENT, ?string $encoding = self::ENCODING_BASE64): mail_interface {
        $this->mail->AddStringAttachment($body, $filename, $encoding, $mimeType);
        return $this;
    }


    public function add_attachment($path, $name = '', $encoding = self::ENCODING_BASE64, $type = '', $disposition = 'attachment'): mail_interface {
        $this->mail->addAttachment($path, $name, $encoding, $type, $disposition);
        return $this;
    }

    /**
     * Adds To-header and recipient, $email can be an array, or a single string
     * address
     *
     * @param  string $email
     * @param  string $name
     * @return mail_interface Provides fluent interface
     */
    public function add_to(string $email, ?string $name = null): mail_interface {
        $this->mail->addAddress($email, $name);
        return $this;
    }

    /**
     * Adds Cc-header and recipient, $email can be an array, or a single string
     * address
     *
     * @param  string    $email
     * @param  string    $name
     * @return mail_interface Provides fluent interface
     */
    public function add_cc(string $email, ?string $name = null): mail_interface {
        $this->mail->addCC($email, $name);
        return $this;
    }

    /**
     * Adds Bcc recipient, $email can be an array, or a single string address
     *
     * @param  string|array    $email
     * @return mail_interface Provides fluent interface
     */
    public function add_bcc(string $email, ?string $name = null): mail_interface {
        $this->mail->addBCC($email, $name);
        return $this;
    }

    /**
     * Sets From-header and sender of the message
     *
     * @param  string    $email
     * @param  string    $name
     * @return mail_interface Provides fluent interface
     * @throws Zend_Mail_Exception if called subsequent times
     */
    public function set_from(string $email, ?string $name = null): mail_interface {
        $this->mail->setFrom($email, $name);
        return $this;
    }


    /**
     * Sets the subject of the message
     *
     * @param   string    $subject
     * @return  mail Provides fluent interface
     * @throws  Zend_Mail_Exception
     */
    public function set_subject(string $subject): mail_interface {
        $this->mail->Subject = $subject;
        return $this;
    }

    public function get_to(): array {
		$config = service_locator::get_config_service();
		if (!$config->is_production()) return [[$config->mail['recipient'], 'Developer']];
		else return $this->mail->getToAddresses();
    }

    public function get_cc(): array {
		$config = service_locator::get_config_service();
		if (!$config->is_production()) return [];
        else return $this->mail->getCcAddresses();
    }

    public function get_bcc(): array {
		$config = service_locator::get_config_service();
		if (!$config->is_production()) return [];
        else return $this->mail->getBccAddresses();
    }

    public function get_html_body(): ?string {
        return $this->mail->Body;
    }

    public function get_text_body(): ?string {
        return $this->mail->AltBody;
    }

    public function get_subject(): ?string {
		$subject = $this->mail->Subject;

		$config = service_locator::get_config_service();
		if (!$config->is_production()) $subject .= ' [DEBUG] '.implode('; ', array_column($this->mail->getToAddresses(), 0));

        return $subject;
    }

    public function get_from(): ?string {
        return $this->mail->From;
    }

    public function add_custom_header(string $header, string $value = ""): void {
        $this->mail->addCustomHeader($header,$value);
    }

    public function has_attachments(): bool {
        return $this->mail->attachmentExists();
    }

    public function get_reply_to(): iterable {
		if (!($rt = $this->mail->getReplyToAddresses())) $rt = [$this->get_from()];
		return $rt;
    }

	public function get_raw_message(): string {
		$config = service_locator::get_config_service();
		if (!$config->is_production()) {
			$this->mail->Subject .= ' [DEBUG] '.implode('; ', array_column($this->mail->getToAddresses(), 0));
			$this->mail->ClearAllRecipients();
			$this->mail->addAddress($config->mail['recipient'], 'Developer');
		}

		// @see: https://github.com/awsdocs/amazon-ses-developer-guide/blob/master/doc-source/examples-send-raw-using-sdk.md
		if (!$this->mail->preSend()) throw new mail_service_exception($mail->mail->ErrorInfo);
            
		return $this->mail->getSentMIMEMessage();
	}

}

