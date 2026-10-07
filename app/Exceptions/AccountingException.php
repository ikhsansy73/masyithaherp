<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Domain exception for accounting rule violations. The message is always
 * user-facing Indonesian (docs/design/03-accounting-core.md).
 */
class AccountingException extends RuntimeException {}
