import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see routes/web.php:14
* @route '/exchange/order'
*/
export const order = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: order.url(options),
    method: 'get',
})

order.definition = {
    methods: ["get","head"],
    url: '/exchange/order',
} satisfies RouteDefinition<["get","head"]>

/**
* @see routes/web.php:14
* @route '/exchange/order'
*/
order.url = (options?: RouteQueryOptions) => {
    return order.definition.url + queryParams(options)
}

/**
* @see routes/web.php:14
* @route '/exchange/order'
*/
order.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: order.url(options),
    method: 'get',
})

/**
* @see routes/web.php:14
* @route '/exchange/order'
*/
order.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: order.url(options),
    method: 'head',
})

/**
* @see routes/web.php:14
* @route '/exchange/order'
*/
const orderForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: order.url(options),
    method: 'get',
})

/**
* @see routes/web.php:14
* @route '/exchange/order'
*/
orderForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: order.url(options),
    method: 'get',
})

/**
* @see routes/web.php:14
* @route '/exchange/order'
*/
orderForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: order.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

order.form = orderForm

const exchange = {
    order: Object.assign(order, order),
}

export default exchange