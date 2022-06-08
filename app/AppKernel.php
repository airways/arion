<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new cspoo\Swiftmailer\MailgunBundle\cspooSwiftmailerMailgunBundle(),
            new AppBundle\AppBundle(),
        ];

        if (in_array($this->getEnvironment(), ['dev', 'test'], true)) {
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
        }

        return $bundles;
    }

    public function getRootDir()
    {
        return __DIR__;
    }

    public function getCacheDir()
    {
        return dirname(__DIR__).'/var/cache/'.$this->getEnvironment();
    }

    public function getLogDir()
    {
        return dirname(__DIR__).'/var/logs';
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->getRootDir().'/config/config_'.$this->getEnvironment().'.yml');
    }
}


function array_set(&$array, $i, $j, $k, &$val)
{
    if($i >= 0 && !array_key_exists($i, $array) || !is_array($array[$i])) $array[$i] = [$val];
    
    if($j >= 0 && !array_key_exists($j, $array[$i]) || !is_array($array[$i][$j])) $array[$i][$j] = [$val];

    if($k >= 0) $array[$i][$j][$k] = $val;

}

function array_let(&$array, $i, $j, $k, &$val)
{
    if(!array_key_exists($i, $array) || !is_array($array[$i])) $array[$i] = $val;
    elseif(!is_array($array[$i][$j])) $array[$i][$j] = $val;
    else $array[$i][$j][$k] = $val;

}

function array_is_assoc($a) {
    foreach(array_keys($a) as $key)
        if (!is_int($key)) return TRUE;
    return FALSE;
}
