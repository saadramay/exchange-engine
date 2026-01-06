import ProfileController from './ProfileController'
import OrderController from './OrderController'
import TradeController from './TradeController'

const Api = {
    ProfileController: Object.assign(ProfileController, ProfileController),
    OrderController: Object.assign(OrderController, OrderController),
    TradeController: Object.assign(TradeController, TradeController),
}

export default Api