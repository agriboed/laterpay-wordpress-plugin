<?php

class BackendModule extends BaseModule {
    //in
    public static $URL = '/wp-admin/';
    public static $usernameField = 'log';
    public static $passwordField = 'pwd';
    public static $usernameValue = 'admin';
    public static $passwordValue = 'password';
    public static $loginButton = 'wp-submit';
    public static $logoutMenu = '#wp-admin-bar-my-account';
    public static $logoutButton = '#wp-admin-bar-logout>a';
    //expected
    public static $expectedBackTitle = 'Dashboard';

    /**
     * Login
     * @return $this
     */
    public function login() {
        $I = $this->BackendTester;

        //Login backend
        $I->amOnPage(BackendModule::$URL);
        $I->see( $I, BackendModule::$expectedBackTitle );
        $I->fillField(BackendModule::$usernameField, BackendModule::$usernameValue);
        $I->fillField(BackendModule::$passwordField, BackendModule::$passwordValue);
        $I->click(BackendModule::$loginButton);

        return $this;
    }

    /**
     * Logout
     * @return $this
     */
    public function logout() {
        $I = $this->BackendTester;

        $I->see( $I, BackendModule::$expectedBackTitle );
        $I->moveMouseOver(BackendModule::$logoutMenu);
        $I->click(BackendModule::$logoutButton);

        return $this;
    }

    /**
     * Price Validation {price input, confirmation link, change link}
     * Is a price validated successfully?
     * @param $price_input
     * @param null $change_link
     * @param null $confirmation_link
     */
    public function validatePrice($price_input, $change_link = null, $confirmation_link = null) {
        $I = $this->BackendTester;

        foreach (SetupModule::$priceValidationArray as $expectedValue => $arrayOfInputValues) {
            foreach ($arrayOfInputValues as $InputValue) {
                if ( $change_link ) {
                    $I->click( $I, $change_link );
                }

                $I->fillField( $price_input, $InputValue );

                if ( $confirmation_link ) {
                    $I->click( $confirmation_link );
                }

                $I->seeInField($price_input, $expectedValue);
            };
        }

        return $this;
    }

}

