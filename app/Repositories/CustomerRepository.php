<?php

namespace App\Repositories;

use App\Models\Customer;
use App\Models\CustomerRecipe;
use App\Models\Recipe;
use Illuminate\Support\Facades\Validator;
use Firebase\JWT\JWT;

class CustomerRepository {
    public function delete($id) {
        try {
            $customer = Customer::find($id);
            if (!$customer) return resultFunction("Err code CR-D: customer not found");

            $customer->delete();

            return resultFunction("Customer is deleted", true);
        } catch (\Exception $e) {
            return resultFunction("Err code CR-D catch: " . $e->getMessage());
        }
    }

    public function update($data) {
        try {
            $validator = Validator::make($data, [
                'id' => 'required',
                'name' => 'required',
                'phone' => 'required'
            ]);
            if ($validator->fails()) return resultFunction('Err code CR-U: validation err ' . $validator->errors());

            $customer = Customer::find($data['id']);
            if (!$customer) return resultFunction("Err code CR-U: customer not found");

            $customer->name = $data['name'];
            $customer->phone = $data['phone'];
            if (isset($data['email'])) {
                if ($data['email']) {
                    $customer->email = $data['email'];
                }
            }
            $customer->save();

            return resultFunction("Customer is updated", true);
        } catch (\Exception $e) {
            return resultFunction("Err code CR-U catch: " . $e->getMessage());
        }
    }

    public function store($data) {
        try {
            $validator = Validator::make($data, [
                'name' => 'required',
                'phone' => 'required'
            ]);
            if ($validator->fails()) return resultFunction('Err code CR-U: validation err ' . $validator->errors());

            $customer = new Customer();

            $customer->name = $data['name'];
            $customer->phone = $data['phone'];
            if (isset($data['email'])) {
                if ($data['email']) {
                    $customer->email = $data['email'];
                }
            }
            $customer->save();

            return resultFunction("Customer is created", true);
        } catch (\Exception $e) {
            return resultFunction("Err code CR-U catch: " . $e->getMessage());
        }
    }

    public function assignRecipe($data) {
        try {
            $validator = Validator::make($data, [
                'customer_id' => 'required',
                'recipe_id' => 'required'
            ]);
            if ($validator->fails()) return resultFunction('Err code CR-AR: validation err ' . $validator->errors());

            $customer = Customer::find($data['customer_id']);
            if (!$customer) return resultFunction("Err code CR-AR: customer not found");

            $recipe = Recipe::find($data['recipe_id']);
            if (!$recipe) return resultFunction("Err code CR-AR: recipe not found");

            $customerRecipe = CustomerRecipe::with([])
                ->where('customer_id', $customer->id)
                ->where('recipe_id', $recipe->id)
                ->first();
            if ($customerRecipe) return resultFunction("Err code CR-AR: the recipe is already assigned to the customer");

            $customerRecipe = new CustomerRecipe();
            $customerRecipe->customer_id = $customer->id;
            $customerRecipe->recipe_id = $recipe->id;
            $customerRecipe->save();

            return resultFunction("Recipes is assigned to customer", true);
        } catch (\Exception $e) {
            return resultFunction("Err code CR-AR catch: " . $e->getMessage());
        }
    }

    public function unassignRecipe($data) {
        try {
            $validator = Validator::make($data, [
                'customer_id' => 'required',
                'recipe_id' => 'required'
            ]);
            if ($validator->fails()) return resultFunction('Err code CR-AR: validation err ' . $validator->errors());

            $customerRecipe = CustomerRecipe::with([])
                ->where('customer_id', $data['customer_id'])
                ->where('recipe_id', $data['recipe_id'])
                ->first();
            if (!$customerRecipe) return resultFunction("Err code CR-AR: the recipe for the customer not found");

            $customerRecipe->delete();

            return resultFunction("Recipes is unassigned to customer", true);
        } catch (\Exception $e) {
            return resultFunction("Err code CR-AR catch: " . $e->getMessage());
        }
    }

    public function detail($id) {
        try {
            $customer = Customer::with(['customer_recipes.recipe.ri_recipe_videos'])->find($id);
            if (!$customer) return resultFunction("Err code CR-De: customer not found");

            return resultFunction("", true, $customer);
        } catch (\Exception $e) {
            return resultFunction("Err code CR-De catch: " . $e->getMessage());
        }
    }

    public function testSignIn($data) {
        try {
            $validator = Validator::make($data, [
                'email' => 'required',
                'password' => 'required'
            ]);
            if ($validator->fails()) return resultFunction('Err code CR-TA: validation err ' . $validator->errors());

            $customer = Customer::with([])
                ->where('email', $data['email'])
                ->first();
            if (!$customer) return resultFunction("Err code CR-TA: customer not found");

            $key = env('JWT_SECRET');
            $payload = [
                "id" => $customer->id
            ];
            $jwt = JWT::encode($payload, $key, 'HS256');
            setcookie('access_token_dbp_web', $jwt, time() + (86400 * 30), "/");

            return resultFunction("Sign in success", true);
        } catch (\Exception $e) {
            return resultFunction("Err code CR-TA catch: " . $e->getMessage());
        }
    }

    public function testAuth($customerId) {
        return resultFunction("Auth success " . $customerId, true);
    }
}