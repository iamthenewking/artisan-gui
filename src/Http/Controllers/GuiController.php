<?php


namespace Infureal\Http\Controllers;


use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Infureal\Providers\GuiServiceProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\BufferedOutput;

class GuiController extends Controller {
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    function index() {
        return view("gui::index");
    }

    function run($command) {
        $command = $this->findCommandOrFail($command);
        $rules = $this->buildRules($command);
        $data = request()->validate($rules);

        $data = array_filter($data);
        $options = array_keys($command->getDefinition()->getOptions());

        $params = [];

        foreach ($data as $key => $value) {

            if (in_array($key, $options))
                $key = "--{$key}";

            $params[$key] = $value;
        }

        $output = new BufferedOutput();
        $status = Artisan::call($command->getName(), $params, $output);

        return back()
            ->with([
                'output' => $output,
                'status' => $status,
                'command' => $command->getName()
            ]);
    }

    protected function findCommandOrFail(string $name): Command {
        $commands = Artisan::all();

        if (!in_array($name, array_keys($commands)))
            abort(404);

        return $commands[$name];
    }

    protected function buildRules(Command $command) {
        $rules = [];

        foreach ($command->getDefinition()->getArguments() as $argument) {
            $rules[$argument->getName()] = [
                $argument->isRequired() ? 'required' : 'nullable',
            ];
        }

        foreach ($command->getDefinition()->getOptions() as $option) {
            $rules[$option->getName()] = [
                $option->isValueRequired() ? 'required' : 'nullable',
                $option->acceptValue() ? 'string' : 'bool',
            ];
        }

        return $rules;
    }

}