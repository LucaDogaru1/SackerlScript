<?php

class Environment
{
    private array $variables = [];
    private array $functions = [];
    private ?Environment $parent = null;

    public function setParent(Environment $parent): void
    {
        $this->parent = $parent;
    }


    public function defineVariable(string $name, $value): void
    {
        $this->variables[$name] = $value;
    }

    /**
     * @throws Exception
     */
    public function getVariable(string $name)
    {
        if (array_key_exists($name, $this->variables)) {
            return $this->variables[$name];
        }

        if ($this->parent != null) {
            return $this->parent->getVariable($name);
        }

        return throw new Exception("Unknown identifier:" . $name);
    }

    public function defineFunction(string $name, $body, $parameters): void
    {
        $this->functions[$name] = [
            "body" => $body,
            "parameters" => $parameters ?? []
        ];
    }

    /**
     * @throws Exception
     */
    public function getFunction(string $name): ?array
    {
        if (array_key_exists($name, $this->functions)) {
            return $this->functions[$name];
        }

        if ($this->parent != null) {
            return $this->parent->getFunction($name);
        }

        return throw new Exception("Unknown identifier:" . $name);
    }


}