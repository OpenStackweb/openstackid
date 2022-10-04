export const emailValidator = (value) => {
    return /^\S+@\S+(\.\S+)*$/.test( value )
}