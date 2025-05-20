<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\Question;
use App\Models\Answer;
use App\Models\QuizResult;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    // 🔎 Affiche tous les quiz avec relations
    public function index()
    {
        return Quiz::with(['course', 'module.course'])->latest()->get();
    }

    // 📄 Affiche un quiz spécifique
    public function show($id)
    {
        $quiz = Quiz::with(['course', 'module.course'])->findOrFail($id);
        return response()->json($quiz);
    }

    // ➕ Créer un quiz (libre ou lié)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'threshold' => 'nullable|integer',
            'module_id' => 'nullable|exists:modules,id',
            'course_id' => 'nullable|exists:courses,id'
        ]);

        \Log::info('Création quiz', $validated);

        $quiz = Quiz::create($validated);
        return response()->json($quiz);
    }

    // ✏️ Modifier un quiz
    public function update(Request $request, $id)
    {
        $quiz = Quiz::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string',
            'threshold' => 'nullable|integer',
        ]);

        $quiz->update($validated);
        return response()->json(['message' => 'Quiz mis à jour']);
    }

    // ❌ Supprimer un quiz
    public function destroy($id)
    {
        $quiz = Quiz::findOrFail($id);
        $quiz->delete();
        return response()->json(['message' => 'Quiz supprimé']);
    }
// 🔎 Obtenir les quiz liés à un module
public function byModule($moduleId)
{
    return Quiz::where('module_id', $moduleId)->get();
}
// 🔎 Obtenir tous les quiz d’un cours (y compris le quiz final)
public function byCourse($courseId)
{
    return Quiz::where('course_id', $courseId)->get();
}


    // ➕ Créer un quiz lié à un module
    public function storeByModule(Request $request, $moduleId)
    {
        \Log::info('storeByModule called', ['moduleId' => $moduleId, 'data' => $request->all()]);

        $validated = $request->validate([
            'title' => 'required|string',
            'threshold' => 'nullable|integer'
        ]);

        $quiz = Quiz::create([
            'title' => $validated['title'],
            'threshold' => $validated['threshold'] ?? 50,
            'module_id' => $moduleId
        ]);

        return response()->json($quiz);
    }

    // 🔁 Récupérer toutes les questions d’un quiz
    public function getQuestions($id)
    {
        return Question::with('answers')
            ->where('quiz_id', $id)
            ->get();
    }

    // ➕ Ajouter une question à un quiz
    public function addQuestion(Request $request, $quizId)
    {
        $validated = $request->validate([
            'question' => 'required|string',
            'type' => 'required|in:mcq,true_false,text',
            'answers' => 'required|array',
            'answers.*.answer' => 'required|string',
            'answers.*.is_correct' => 'required|boolean'
        ]);

        $question = Question::create([
            'quiz_id' => $quizId,
            'question' => $validated['question'],
            'type' => $validated['type']
        ]);

        foreach ($validated['answers'] as $ans) {
            Answer::create([
                'question_id' => $question->id,
                'answer' => $ans['answer'],
                'is_correct' => $ans['is_correct']
            ]);
        }

        return response()->json(['message' => 'Question ajoutée']);
    }

    // ✅ Soumettre les réponses d’un étudiant
    public function submitAnswers(Request $request, $quizId)
    {
         \Log::info("🧪 submitAnswers() appelé avec quiz ID = $quizId");
        $userId = auth()->id();
        $quiz = Quiz::findOrFail($quizId);
        $answers = $request->input('answers'); // format : [question_id => selected_answer]

        $score = 0;
        $total = 0;

        foreach ($answers as $questionId => $selected) {
            $question = Question::with('answers')->find($questionId);
            if (!$question) continue;

            $correctAnswer = $question->answers->firstWhere('is_correct', true);

            if ($question->type === 'mcq' && $selected == $correctAnswer?->id) {
                $score++;
            } elseif ($question->type === 'true_false' && strtolower($selected) === strtolower($correctAnswer?->answer)) {
                $score++;
            }

            // Les réponses textuelles peuvent être traitées ultérieurement
            $total++;
        }

        $finalScore = $total > 0 ? round(($score / $total) * 100) : 0;
        $passed = $finalScore >= $quiz->threshold;

   QuizResult::updateOrCreate(
    ['user_id' => $userId, 'quiz_id' => $quizId],
    ['score' => $finalScore, 'passed' => $passed]
);


        return response()->json([
            'score' => $finalScore,
            'passed' => $passed
        ]);
    }
}
