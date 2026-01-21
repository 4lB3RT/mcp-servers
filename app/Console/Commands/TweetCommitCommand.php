<?php

namespace App\Console\Commands;

use App\Services\TwitterService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TweetCommitCommand extends Command
{
    protected $signature = 'twitter:commit
                            {--path= : Git repository path (default: current directory)}
                            {--commits=1 : Number of recent commits to include}
                            {--dry-run : Show tweet without posting}';

    protected $description = 'Generate and post a storytelling tweet about your recent git commits';

    public function handle(TwitterService $twitter): int
    {
        $path = $this->option('path') ?: getcwd();
        $numCommits = (int) $this->option('commits');
        $dryRun = $this->option('dry-run');

        // Get recent commits
        $commits = $this->getRecentCommits($path, $numCommits);

        if (empty($commits)) {
            $this->error('No commits found in the repository.');
            return 1;
        }

        $this->info('Recent commits:');
        foreach ($commits as $commit) {
            $this->line("  - {$commit['message']} ({$commit['hash']})");
        }

        // Generate storytelling tweet
        $tweet = $this->generateStoryTweet($commits);

        $this->newLine();
        $this->info('Generated tweet:');
        $this->line($tweet);
        $this->line('Characters: ' . strlen($tweet) . '/280');

        if ($dryRun) {
            $this->warn('Dry run - tweet not posted.');
            return 0;
        }

        if (!$this->confirm('Post this tweet?')) {
            $this->warn('Tweet cancelled.');
            return 0;
        }

        // Post tweet
        $result = $twitter->tweet($tweet);

        if (isset($result['data']['id'])) {
            $this->info('Tweet posted successfully!');
            $this->line("Tweet ID: {$result['data']['id']}");
            return 0;
        }

        $this->error('Failed to post tweet: ' . json_encode($result));
        return 1;
    }

    private function getRecentCommits(string $path, int $num): array
    {
        $output = [];
        $format = '%H|||%s|||%an|||%ar';
        exec("cd {$path} && git log -{$num} --pretty=format:'{$format}'", $output);

        $commits = [];
        foreach ($output as $line) {
            $parts = explode('|||', $line);
            if (count($parts) >= 4) {
                $commits[] = [
                    'hash' => substr($parts[0], 0, 7),
                    'message' => $parts[1],
                    'author' => $parts[2],
                    'time' => $parts[3],
                ];
            }
        }

        return $commits;
    }

    private function generateStoryTweet(array $commits): string
    {
        // Get changed files for context
        $path = $this->option('path') ?: getcwd();
        $files = $this->getChangedFiles($path, count($commits));

        // Build context for storytelling
        $commitMessages = array_map(fn($c) => $c['message'], $commits);
        $context = implode(', ', $commitMessages);

        // Simple storytelling templates based on commit type
        $tweet = $this->createStoryFromCommits($commits, $files);

        // Ensure tweet is within limit
        if (strlen($tweet) > 280) {
            $tweet = substr($tweet, 0, 277) . '...';
        }

        return $tweet;
    }

    private function getChangedFiles(string $path, int $numCommits): array
    {
        $output = [];
        exec("cd {$path} && git diff --name-only HEAD~{$numCommits} HEAD 2>/dev/null", $output);
        return $output;
    }

    private function createStoryFromCommits(array $commits, array $files): string
    {
        $firstCommit = $commits[0];
        $message = strtolower($firstCommit['message']);

        // Detect type of work
        $type = match (true) {
            str_contains($message, 'fix') => 'fix',
            str_contains($message, 'feat') || str_contains($message, 'add') => 'feature',
            str_contains($message, 'refactor') => 'refactor',
            str_contains($message, 'test') => 'test',
            str_contains($message, 'doc') => 'docs',
            str_contains($message, 'style') || str_contains($message, 'css') => 'style',
            default => 'update',
        };

        // Get project name from path
        $path = $this->option('path') ?: getcwd();
        $project = basename($path);

        // Build storytelling tweet
        $templates = [
            'fix' => [
                "🔧 Just squashed a bug in {$project}! {$firstCommit['message']} #coding #developer",
                "🐛 Bug hunting session complete! Fixed: {$firstCommit['message']} #programming",
            ],
            'feature' => [
                "🚀 New feature alert! Just shipped: {$firstCommit['message']} #buildinpublic #coding",
                "✨ Building something cool! {$firstCommit['message']} #developer #coding",
            ],
            'refactor' => [
                "🧹 Code cleanup day! {$firstCommit['message']} #cleancode #refactoring",
                "♻️ Making the codebase better, one refactor at a time. {$firstCommit['message']}",
            ],
            'test' => [
                "🧪 Adding tests to {$project}! Quality matters. #testing #tdd",
                "✅ Test coverage improved! {$firstCommit['message']} #qualitycode",
            ],
            'docs' => [
                "📝 Documentation matters! Updated docs for {$project}. #documentation",
                "📚 Better docs = happier developers. {$firstCommit['message']}",
            ],
            'style' => [
                "🎨 Making things pretty! {$firstCommit['message']} #frontend #design",
                "💅 Style updates for {$project}! {$firstCommit['message']}",
            ],
            'update' => [
                "💻 Progress on {$project}: {$firstCommit['message']} #coding #buildinpublic",
                "🔨 Working on {$project}! {$firstCommit['message']} #developer",
            ],
        ];

        $options = $templates[$type] ?? $templates['update'];
        return $options[array_rand($options)];
    }
}
