<?php

const PINQ_ASC = \DrSlump\Pinq::ASC;
const PINQ_DESC = \DrSlump\Pinq::DESC;

/**
 *
 * @param array|Iterator $data
 * @return \DrSlump\Pinq
 */
function pinq($data) {
    return new \DrSlump\Pinq($data);
}

// Register autoloader
\DrSlump\Pinq::autoload();
