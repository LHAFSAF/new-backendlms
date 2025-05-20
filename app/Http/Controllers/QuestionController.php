<?php
namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Answer;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function update(Request $request, $id)
    {
        $question = Question::findOrFail($id);

        $validated = $request->validate([
            'question' => 'required|string',
            'type' => 'required|in:mcq,true_false,text',
            'answers' => 'required|array',
            'answers.*.answer' => 'required|string',
            'answers.*.is_correct' => 'required|boolean'
        ]);

        $question->update([
            'question' => $validated['question'],
            'type' => $validated['type']
        ]);

        // Supprimer anciennes réponses et réinsérer
        $question->answers()->delete();

        foreach ($validated['answers'] as $ans) {
            Answer::create([
                'question_id' => $question->id,
                'answer' => $ans['answer'],
                'is_correct' => $ans['is_correct']
            ]);
        }

        return response()->json(['message' => 'Question mise à jour']);
    }

    public function destroy($id)
    {
        $question = Question::findOrFail($id);
        $question->answers()->delete();
        $question->delete();

        return response()->json(['message' => 'Question supprimée']);
    }
}
