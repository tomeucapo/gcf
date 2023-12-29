<?php
namespace gcf\utils;

use gcf\utils\iban\IBANGenerator;

class NumeroCompte
{
    private string $entitat;
    private string $oficina;
    private string $dc;
    private string $compte;

    /**
     * NumeroCompte constructor.
     * @param string $entitat
     * @param string $oficina
     * @param string $dc
     * @param string $compte
     */
    public function __construct(string $entitat, string $oficina, string $dc, string $compte)
    {
        $this->entitat = $entitat;
        $this->oficina = $oficina;
        $this->dc = $dc;
        $this->compte = $compte;
    }

    /**
     * @param string $ccc
     * @return NumeroCompte
     * @throws \Exception
     */
    public static function CreateFromString(string $ccc)
    {
        if (!preg_match("/([0-9]{4})\-([0-9]{4})\-([0-9]{2})\-([0-9]{10})/", $ccc, $cc_bancaria))
        {
            throw new \Exception("EL format de la compte bancaria és incorrecte");
        }
        $entitat = $cc_bancaria[1]; //digits entitat bancaria;
        $oficina = $cc_bancaria[2]; //ditigs oficina
        $dc = $cc_bancaria[3]; //digits control
        $compte = $cc_bancaria[4]; //digits compte

        return new NumeroCompte($entitat, $oficina, $dc, $compte);
    }

    /**
     * @return bool
     */
    public function Valid() : bool
    {
        $entitat = str_split($this->entitat); //converteix string en un array de caracters
        $entitat = array_map("intval", $entitat); //sustitueix cada caracter per la seva representació de tipus int
        $oficina = str_split($this->oficina);
        $oficina = array_map("intval", $oficina);
        $compte = str_split($this->compte);
        $compte = array_map("intval", $compte);

        $sumaDC1 = 4*$entitat[0] + 8*$entitat[1] + 5*$entitat[2] + 10*$entitat[3] + 9*$oficina[0] + 7*$oficina[1] + 3*$oficina[2] + 6*$oficina[3];
        $expectedDC1 = $this->getDigitControl($sumaDC1);

        if ($this->dc[0] != $expectedDC1)
            return false;

        $sumaDC2 = $compte[0] + 2*$compte[1] + 4*$compte[2] + 8*$compte[3] + 5*$compte[4] + 10*$compte[5] + 9*$compte[6] + 7*$compte[7] + 3*$compte[8] + 6*$compte[9];
        $expectedDC2 = $this->getDigitControl($sumaDC2);

        return $this->dc[1] == $expectedDC2;
    }


    /**
     * El dígito de control es la diferencia entre 11 y el resto de la división de la suma entre 11,
     * salvo que la diferencia sea 11, en cuyo caso el dígito de control es 0 o que la diferencia sea 10,
     * en cuyo caso el dígito de control es 1.
     * @param int $suma
     * @return int
     */
    private function GetDigitControl(int $suma) : int
    {
        $digit = 11 - ($suma % 11);
        if ($digit == 11)
            return 0;

        if ($digit == 10)
            return 1;

        return $digit;
    }

    /**
     * @param string $locale
     * @return string
     */
    public function GetIBAN(string $locale = 'ES') : string
    {
        $IBANGenerator = new IBANGenerator($this->entitat.$this->oficina, $this->dc.$this->compte, $locale);
        return $IBANGenerator->generate();
    }

    /**
     * @return string
     */
    public function GetCompteCorrent() : string
    {
        return $this->entitat."-".$this->oficina."-".$this->dc."-".$this->compte;
    }

    /**
     * @return string
     */
    public function GetEntitat(): string
    {
        return $this->entitat;
    }

    /**
     * @return string
     */
    public function GetOficina(): string
    {
        return $this->oficina;
    }

    /**
     * @return string
     */
    public function GetDC(): string
    {
        return $this->dc;
    }

    /**
     * @return string
     */
    public function GetNumeroCompte(): string
    {
        return $this->compte;
    }

}

