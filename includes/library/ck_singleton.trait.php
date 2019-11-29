<?php 

trait ck_singleton_trait {
    protected static $instance = null;

    public static function instance(?array $parameters = null): self {
        if(self::$instance===null) self::$instance = new self($parameters);
        return self::$instance;
    }
}

