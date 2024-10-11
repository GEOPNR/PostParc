<?php

namespace PostparcBundle\Service;

use PostparcBundle\Entity\User;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class UserConfigService 
{
    
    public function __construct() {
        
    }
    
    public function getSummerNoteFontFamily(User $user)
    {
        $summernote_font_family = null;
        if(isset($user->getEntity()->getConfigs()['summernote_font_family']) ){
            $summernote_font_family = $user->getEntity()->getConfigs()['summernote_font_family'];
        }
        if (isset($user->getConfigs()['summernote_font_family'])){
            $summernote_font_family = $user->getConfigs()['summernote_font_family'];
        }
        
        return $summernote_font_family;
    }

    public function getSummerNoteFontSize(User $user)
    {
        $summernote_font_size = null;
        if(isset($user->getEntity()->getConfigs()['summernote_font_size']) ){
            $summernote_font_size = $user->getEntity()->getConfigs()['summernote_font_size'];
        }
        if (isset($user->getConfigs()['summernote_font_size'])){
            $summernote_font_size=  $user->getConfigs()['summernote_font_size'];
        }
        
        return $summernote_font_size;
    }    
}
