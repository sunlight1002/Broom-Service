export const leadStatusColor = (_status) => {
    let _color = "";
    switch (_status) {
        case "pending":
            _color = "#ffa500";
            break;

        case "potential":
            _color = "lightblue";
            break;

        case "irrelevant":
            _color = "orange";
            break;

        case "uninterested":
            _color = "red";
            break;

        case "unanswered":
            _color = "purple";
            break;

        case "potential client":
            _color = "pink";
            break;

        case "pending client":
            _color = "purple";
            break;

        case "freeze client":
            _color = "#b98787";
            break;

        case "active client":
            _color = "green";
            break;

        case "past":
            _color = "black";
            break;

        default:
            break;
    }

    return { backgroundColor: _color };
};
