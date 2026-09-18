<?php
/*
 * EduRank - exemplo de configuração da senha administrativa.
 *
 * 1. Copie este arquivo para "config.php".
 * 2. Gere o hash da sua senha (a senha em texto puro NÃO fica salva):
 *
 *      php -r "echo password_hash('suaSenha', PASSWORD_DEFAULT), PHP_EOL;"
 *
 * 3. Cole o resultado em $PASSWORD_HASH, mantendo as aspas simples.
 *
 * O arquivo config.php está no .gitignore e nunca deve ser publicado.
 */

$PASSWORD_HASH = 'COLE_AQUI_O_HASH_GERADO';
