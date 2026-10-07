<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Domain exception for school-domain rule violations. The message is
 * always user-facing Indonesian (docs/design/06-students-academics.md).
 */
class SchoolException extends RuntimeException {}
