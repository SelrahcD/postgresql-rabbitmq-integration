<?php

function getEnvOrDefault(string $varName, $default) {
    return getenv($varName) !== false ? getenv($varName) : $default;
}
