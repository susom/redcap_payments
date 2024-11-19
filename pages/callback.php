<?php

namespace Stanford\REDCapPayments;

/** @var \Stanford\REDCapPayments\REDCapPayments $module */

try{
    $module->processCallback();
}catch (\Exception $e){
    echo $e->getMessage();
}