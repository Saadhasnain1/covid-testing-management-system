<?php
// Retained URL; registration validation is now performed by the request controller.
// Public identity enumeration is deliberately unavailable.
require __DIR__.'/includes/bootstrap.php';
http_response_code(410);
header('Content-Type: application/json');
echo json_encode(['message'=>'Submit the request form to validate your details.']);
