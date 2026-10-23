<?php
// Optional secure AI integration. Set OPENAI_API_KEY in your hosting environment.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }
$key = getenv('OPENAI_API_KEY');
if (!$key) { http_response_code(503); echo json_encode(['error'=>'AI not configured; website assistant fallback remains available']); exit; }
$input = json_decode(file_get_contents('php://input'), true);
$message = trim((string)($input['message'] ?? ''));
if (!$message || strlen($message) > 1600) { http_response_code(400); echo json_encode(['error'=>'Invalid message']); exit; }
$instructions = 'You are MIKE, a helpful virtual assistant for Imperial Youths Advocates Federation (IYAF), a youth advocacy and sustainable development organization founded in 2024. Help visitors understand youth leadership, SDGs, advocacy, education, community action, programs and how to get involved. Contact: iyafzim003@gmail.com, WhatsApp +263 78 620 7114. Do not invent specific events, leadership appointments, membership requirements, prices, or statistics. Direct users to the website for current details. Never claim to be human. Be concise and helpful.';
$payload = json_encode(['model'=>'gpt-4.1-mini','instructions'=>$instructions,'input'=>$message,'max_output_tokens'=>350]);
$ch = curl_init('https://api.openai.com/v1/responses');
curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$payload,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>18,CURLOPT_HTTPHEADER=>['Content-Type: application/json','Authorization: Bearer '.$key]]);
$result = curl_exec($ch); $status = curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
$data = json_decode($result ?: '',true);
if ($status < 200 || $status >= 300 || !is_array($data)) {http_response_code(502);echo json_encode(['error'=>'AI service unavailable']);exit;}
$reply = '';
foreach (($data['output'] ?? []) as $item) foreach (($item['content'] ?? []) as $part) if (($part['type'] ?? '') === 'output_text') $reply .= ($part['text'] ?? '');
if (!$reply) {http_response_code(502);echo json_encode(['error'=>'No AI reply']);exit;}
echo json_encode(['reply'=>$reply]);
