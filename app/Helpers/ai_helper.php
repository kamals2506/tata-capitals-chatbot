function scoreCall($transcript)
{
    $prompt = "
    Evaluate sales call and return JSON:

    {
      score: number,
      feedback: text
    }

    Call:
    $transcript
    ";

    return callOllama($prompt);
}s