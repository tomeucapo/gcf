<?php
namespace gcf\auth;

/**
 * @property string NomP Nom del usuari
 * @property string LliP Llinatges del usuari
 * @property string Nom Nom comú del usuari (Common Name)
 */
class userLDAP extends userPlugin
{
    const PROTOCOL_VERSION = 3;

    private mixed $connect;

    private string $baseDN;

    /**
     * userLDAP constructor.
     * @param $ldapServer
     * @param $baseDN
     * @param null $user
     * @param null $passwd
     * @throws errorLDAP
     * @throws errorLDAPBind
     * @throws errorLDAPConn
     */
    public function __construct(string $ldapServer, string $baseDN, ?string $user=null, ?string $passwd=null)
    {
           $this->baseDN = $baseDN;

           if (!function_exists("ldap_connect"))
              throw new errorLDAP("El PHP no te habilitat el soport de LDAP!");

           if (!($this->connect=ldap_connect($ldapServer)))
              throw new errorLDAPConn("Error al connectar al servidor LDAP: $ldapServer");

           ldap_set_option($this->connect, LDAP_OPT_PROTOCOL_VERSION, self::PROTOCOL_VERSION);
           ldap_set_option($this->connect, LDAP_OPT_REFERRALS, 0);

           if (!empty($user) && !empty($passwd)) {
              if (!ldap_bind($this->connect, $user, $passwd))
                 throw new errorLDAPBind("Error al fer el bind al LDAP: $ldapServer");
           } else {
              if (!ldap_bind($this->connect))
                 throw new errorLDAPBind("Error al fer el bind al LDAP: $ldapServer");
           }
    }

    /**
     * @param $nick
     * @return bool
     * @throws errorLDAPSearch
     */
    public function getUser(string $nick) : bool
    {
           if (!($search=@ldap_search($this->connect,$this->baseDN,"uid=$nick"))) 
              throw new errorLDAPSearch("Error en cercar objecte (uid=$nick): {$this->baseDN}");

           $number = @ldap_count_entries($this->connect, $search);

           if ($number === 0)
               return false;

           $info = @ldap_get_entries($this->connect, $search);
    
           $this->userInfo = ["nick" => $nick,
                              "Nom"  => $info[0]["cn"][0],
                              "UID"  => (int)$info[0]["uidnumber"][0],
                              "GID"  => array_key_exists("gidnumber", $info[0]) ? $info[0]["gidnumber"][0] : null,
                              "NomP" => $info[0]["givenname"][0],
                              "LliP" => $info[0]["sn"][0]];

           return true;
	}

    /**
     * @return int
     * @throws errorLDAPSearch
     */
    public function getUsersList() : int
    {
	       if (!($search=ldap_search($this->connect,$this->baseDN,"(&(objectclass=person)(uidNumber=*))")))
               throw new errorLDAPSearch("Error en llistar els usuaris de LDAP: ".$this->baseDN);

           $number = ldap_count_entries($this->connect, $search);

           $this->users = [];
           if ($number > 0)
           {
               $info = ldap_get_entries($this->connect, $search);
               for($entry=0;$entry<=$number-1;$entry++) 
               {
                   $uid = $info[$entry]["uidnumber"][0];
                   $dataEntry = [];
                   foreach ($info[$entry] as $key => $value) 
                   {
                          $dataEntry[$key] = $value[0];
                   }
            
                   $this->users[$uid] = $dataEntry;
               }
           }

           return $number;
    }

    public function __destruct()
    {
           if ($this->connect)
              ldap_close($this->connect);
    }
}