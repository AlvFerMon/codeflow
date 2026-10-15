<?php

namespace App\Ai;

use RuntimeException;

/** El servicio de IA no está disponible o ha respondido con un error. El mensaje se muestra al usuario. */
class AiUnavailableException extends RuntimeException {}
