<?php


$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__.'/lib/')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR1' => true,
        '@PSR2' => true,
        //'@PER-CS' => true,
        //'@PHP8x2Migration' => true,
        'simplified_null_return' => false
    ])
    ->setFinder($finder)
    ;

?>
