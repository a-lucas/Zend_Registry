Zend_Registry
=============

This package makes it easier to deal with session &amp; cookies.


You can only use this class into controllers and Models. Otherwise, this will thorw you an Exception.



Configuration options
---------------------

In the applications.ini, you can set up these options : 


    registry.order: LCS
    ; 'L' =>local, 'C' => cookies, 'S' => session
    ; LCS means that on Load, Local variables will always override cookies , and cookies overides sessions.

    ;TO BE IMPLEMENTED
    registry.cookie_expiration= 3600
    registry.session_expiration= 3600
    registry.persistent_expiration= 3600
    registry.cookie_expire_on_close= false
    registry.session_expire_on_close= false
    registry.persistent_expire_on_close= false
    registry.encrypt= false ;// We don't encrypt data
    registry.matchIP= false ; We don't match IP
    registry.matchUserAgent= false


Usage
-----

  $registry = new Zend_Registry_Store();
  //user make a search
  $registry->set("LastSearch", $searchCriteria, Zend_Registry_Store::cookie);
  //User logins
  $registry->set("LastLoginDate", @date("d-m-Y H:i:s"), Zend_Registry_Store::session);            
  if($registry->has("LastSearch")){
    //remove the cookie, and store the data in a session instead
    $lastSearchCriteria = $registry->LastSearch;
    $registry->set("LastSearch", $lastSearchCriteria, Zend_Registry_Store::session);
    //or
    $registry->set("LastSearch", $registry->LastSearch, Zend_Registry_Store::session);
  }

    //User Logout

    $registry->set("LastDeconnectionDate", @date("d-m-Y H:i:s"), Zend_Registry_Store::cookie);
    if($registry->has("LastSearch")){
       $registry->set("LastSearch", $registry->LastSearch, Zend_Registry_Store::cookie);
    }
