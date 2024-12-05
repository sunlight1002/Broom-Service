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

        case "unanswered_final":
            _color = "#5d5d5d";
            break;

        case "reschedule call":
            _color = "#5584c7";
            break;

        case "potential client":
            _color = "pink";
            break;

        case "pending client":
            _color = "purple";
            break;

        case "waiting":
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

        case "approved":
            _color = "green";
            break;

        case "completed":
            _color = "green";
            break;

        case "rejected":
            _color = "#d51212";
            break;
        case "unhappy":
            _color = "red";
            break;

        case "price issue":
            _color = "#770000";
            break;

        case "moved":
            _color = "#4561ab";
            break;

        case "one-time":
            _color = "#626567";
            break;

        default:
            break;
    }

    return { backgroundColor: _color };
};
