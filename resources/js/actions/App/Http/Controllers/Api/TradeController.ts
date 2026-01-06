import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\TradeController::index
* @see app/Http/Controllers/Api/TradeController.php:12
* @route '/api/trades'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/trades',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\TradeController::index
* @see app/Http/Controllers/Api/TradeController.php:12
* @route '/api/trades'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\TradeController::index
* @see app/Http/Controllers/Api/TradeController.php:12
* @route '/api/trades'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\TradeController::index
* @see app/Http/Controllers/Api/TradeController.php:12
* @route '/api/trades'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\TradeController::index
* @see app/Http/Controllers/Api/TradeController.php:12
* @route '/api/trades'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\TradeController::index
* @see app/Http/Controllers/Api/TradeController.php:12
* @route '/api/trades'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\TradeController::index
* @see app/Http/Controllers/Api/TradeController.php:12
* @route '/api/trades'
*/
indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

/**
* @see \App\Http\Controllers\Api\TradeController::myTrades
* @see app/Http/Controllers/Api/TradeController.php:45
* @route '/api/my/trades'
*/
export const myTrades = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: myTrades.url(options),
    method: 'get',
})

myTrades.definition = {
    methods: ["get","head"],
    url: '/api/my/trades',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\TradeController::myTrades
* @see app/Http/Controllers/Api/TradeController.php:45
* @route '/api/my/trades'
*/
myTrades.url = (options?: RouteQueryOptions) => {
    return myTrades.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\TradeController::myTrades
* @see app/Http/Controllers/Api/TradeController.php:45
* @route '/api/my/trades'
*/
myTrades.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: myTrades.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\TradeController::myTrades
* @see app/Http/Controllers/Api/TradeController.php:45
* @route '/api/my/trades'
*/
myTrades.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: myTrades.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\TradeController::myTrades
* @see app/Http/Controllers/Api/TradeController.php:45
* @route '/api/my/trades'
*/
const myTradesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: myTrades.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\TradeController::myTrades
* @see app/Http/Controllers/Api/TradeController.php:45
* @route '/api/my/trades'
*/
myTradesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: myTrades.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\TradeController::myTrades
* @see app/Http/Controllers/Api/TradeController.php:45
* @route '/api/my/trades'
*/
myTradesForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: myTrades.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

myTrades.form = myTradesForm

const TradeController = { index, myTrades }

export default TradeController