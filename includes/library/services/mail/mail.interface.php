<?php 

interface mail_interface {

    const TYPE_OCTETSTREAM = 'application/octet-stream';
    const TYPE_TEXT = 'text/plain';
    const TYPE_HTML = 'text/html';
    const MULTIPART_ALTERNATIVE = 'multipart/alternative';
    const MULTIPART_MIXED = 'multipart/mixed';
    const MULTIPART_RELATED = 'multipart/related';

    const ENCODING_7BIT = '7bit';
    const ENCODING_8BIT = '8bit';
    const ENCODING_QUOTEDPRINTABLE = 'quoted-printable';
    const ENCODING_BASE64 = 'base64';
    const DISPOSITION_ATTACHMENT = 'attachment';
    const DISPOSITION_INLINE = 'inline';

    public function set_body(?string $html = null, ?string $text = null): mail_interface;


    /**
     * Creates a Zend_Mime_Part attachment
     *
     * Attachment is automatically added to the mail object after creation. The
     * attachment object is returned to allow for further manipulation.
     *
     * @param  string         $body
     * @param  string         $mimeType
     * @param  string         $disposition
     * @param  string         $encoding
     * @param  string         $filename OPTIONAL A filename for the attachment
     * @return mail_interface Newly created Zend_Mime_Part object (to allow
     * advanced settings)
     */
    public function create_attachment(string $body, ?string $filename = null, ?string $mimeType = self::TYPE_OCTETSTREAM, ?string $disposition = self::DISPOSITION_ATTACHMENT, ?string $encoding = self::ENCODING_BASE64): mail_interface;
    
    public function add_attachment($path, $name = '', $encoding = self::ENCODING_BASE64, $type = '', $disposition = 'attachment'): mail_interface;

    /**
     * Adds To-header and recipient, $email can be an array, or a single string
     * address
     *
     * @param  string $email
     * @param  string $name
     * @throws mail_service_exception on invalid email
     * @return  mail_interface Provides fluent interface
     */
    public function add_to(string $email, ?string $name = null): mail_interface;
    /**
     * Adds Cc-header and recipient, $email can be an array, or a single string
     * address
     *
     * @param  string    $email
     * @param  string    $name
     * @throws mail_service_exception on invalid email
     * @return  mail_interface Provides fluent interface
     */
    public function add_cc(string $email, ?string $name = null): mail_interface;
    /**
     * Adds Bcc recipient, $email can be an array, or a single string address
     *
     * @param  array    $email
     * @throws mail_service_exception on invalid email
     * @return  mail_interface Provides fluent interface
     */
    public function add_bcc(string $email, ?string $name = null): mail_interface;
    /**
     * Sets From-header and sender of the message
     *
     * @param  string    $email
     * @param  string    $name
     * @return  mail_interface Provides fluent interface
     * @throws mail_service_exception on invalid email
     * @throws mail_service_exception if called subsequent times
     */
    public function set_from(string $email, ?string $name = null): mail_interface;
    /**
     * Sets the subject of the message
     *
     * @param   string    $subject
     * @return  mail Provides fluent interface
     * @throws  mail_service_exception
     */
    public function set_subject(string $subject): mail_interface;

    public function get_to(): iterable;

    public function get_cc(): iterable;

    public function get_bcc(): iterable;

    public function get_html_body(): ?string;

    public function get_text_body(): ?string;

    public function get_subject(): ?string;

    public function get_from(): ?string;

    public function add_custom_header(string $header, string $value = ""): void;

    public function has_attachments(): bool;

    public function get_reply_to(): iterable;

}

