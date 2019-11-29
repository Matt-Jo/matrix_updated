<?php 

/**
 * Manage mails priority queue
 */
interface mail_service_interface extends service_interface {

    const PRIORITY_LOW = 1;
    const PRIORITY_NORMAL = 2;
    const PRIORITY_HIGH = 3;

    /**
     * Factory method
     *
     * @return mail_interface
     */
    public function create_mail(): mail_interface;

    /**
     * Send the given mail
     *
     * @param mail_interface $mail
     * @return void
     */
    public function send(mail_interface $mail): bool;

    public static function validate_address(string $mail): bool;

}

