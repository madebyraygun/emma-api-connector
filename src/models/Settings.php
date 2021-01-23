<?php 
namespace madebyraygun\emmaapiconnector\models;

use craft\base\Model;

class Settings extends Model {
    public $emmaAccount = null;
    public $emmaPrivateKey = null;
    public $emmaPublicKey = null;
    public $emmaGroup = null;
    public $emmaSignupFormId = null;
}
